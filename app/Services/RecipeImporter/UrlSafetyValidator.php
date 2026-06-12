<?php

declare(strict_types=1);

namespace App\Services\RecipeImporter;

use App\Exceptions\BlockedUrlException;
use Closure;

class UrlSafetyValidator
{
    public function __construct(private ?Closure $resolver = null) {}

    /**
     * Validate that the given URL is safe to fetch.
     *
     * @throws BlockedUrlException If the URL is not safe
     */
    public function validate(string $url): void
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new BlockedUrlException;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (empty($host)) {
            throw new BlockedUrlException;
        }

        // Strip IPv6 brackets: [::1] -> ::1
        $ipLiteral = ltrim(rtrim($host, ']'), '[');

        // If host is already a valid IP literal, validate it directly without DNS
        if (filter_var($ipLiteral, FILTER_VALIDATE_IP) !== false) {
            if (! $this->isPublicIp($ipLiteral)) {
                throw new BlockedUrlException;
            }

            return;
        }

        // Perform DNS resolution
        $ips = ($this->resolver ?? $this->defaultResolver())($host);

        if (empty($ips)) {
            throw new BlockedUrlException('This URL is not allowed.');
        }

        foreach ($ips as $ip) {
            if (! $this->isPublicIp($ip)) {
                throw new BlockedUrlException;
            }
        }
    }

    /**
     * Returns true only if the IP is a publicly routable address.
     */
    private function isPublicIp(string $ip): bool
    {
        // Reject unspecified addresses
        if ($ip === '0.0.0.0' || $ip === '::') {
            return false;
        }

        // Reject loopback addresses explicitly
        if ($ip === '::1') {
            return false;
        }

        // IPv4 loopback: 127.x.x.x
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts = explode('.', $ip);
            if ((int) $parts[0] === 127) {
                return false;
            }
        }

        // Link-local: 169.254.x.x (including 169.254.169.254 cloud metadata)
        if (str_starts_with($ip, '169.254.')) {
            return false;
        }

        // IPv6 link-local: fe80::/10
        if (preg_match('/^fe[89ab][0-9a-f]:/i', $ip)) {
            return false;
        }

        // filter_var rejects private and reserved ranges
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        return true;
    }

    /**
     * Default resolver: returns all A and AAAA records for the host.
     *
     * @return array<string>
     */
    private function defaultResolver(): Closure
    {
        return function (string $host): array {
            $ipv4 = gethostbynamel($host);
            $ips = is_array($ipv4) ? $ipv4 : [];

            $aaaaRecords = dns_get_record($host, DNS_AAAA);
            if (is_array($aaaaRecords)) {
                foreach ($aaaaRecords as $record) {
                    if (isset($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }

            return $ips;
        };
    }
}
