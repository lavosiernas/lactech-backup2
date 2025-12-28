/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  swcMinify: true,
  output: 'standalone',
  env: {
    NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:3000/api',
    NEXT_PUBLIC_BASE_URL: process.env.NEXT_PUBLIC_BASE_URL || 'http://localhost:3000',
  },
  async rewrites() {
    return [
      {
        source: '/api/php/:path*',
        destination: '/api/php-proxy/:path*',
      },
    ];
  },
  images: {
    domains: ['safenode.cloud', 'localhost', 'i.postimg.cc'],
    unoptimized: true,
  },
  // Servir arquivos estáticos da pasta assets
  async headers() {
    return [
      {
        source: '/assets/:path*',
        headers: [
          {
            key: 'Cache-Control',
            value: 'public, max-age=31536000, immutable',
          },
        ],
      },
    ];
  },
}

module.exports = nextConfig

