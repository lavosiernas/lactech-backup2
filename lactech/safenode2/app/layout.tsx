import type { Metadata } from 'next'
import { Inter } from 'next/font/google'
import './globals.css'

const inter = Inter({ subsets: ['latin'] })

export const metadata: Metadata = {
  title: 'SafeNode - Security Platform',
  description: 'Plataforma de segurança avançada para proteção de sites',
  icons: {
    icon: '/assets/img/logos (6).png',
    shortcut: '/assets/img/logos (6).png',
    apple: '/assets/img/logos (6).png',
  },
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="pt-BR" className="dark">
      <body className={`${inter.className} h-full`}>{children}</body>
    </html>
  )
}

