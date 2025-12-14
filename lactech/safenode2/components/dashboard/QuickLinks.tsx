'use client'

import Link from 'next/link'
import { Brain, Lightbulb, AlertOctagon, Target } from 'lucide-react'

export default function QuickLinks() {
  const links = [
    {
      icon: Brain,
      label: 'Análise Comportamental',
      href: '/behavior-analysis',
      description: 'IPs com comportamento suspeito',
      color: 'violet',
    },
    {
      icon: Lightbulb,
      label: 'Security Analytics',
      href: '/security-analytics',
      description: 'Análises avançadas e insights',
      color: 'amber',
    },
    {
      icon: AlertOctagon,
      label: 'IPs Suspeitos',
      href: '/suspicious-ips',
      description: 'IPs com múltiplos ataques',
      color: 'red',
    },
    {
      icon: Target,
      label: 'Alvos Atacados',
      href: '/attacked-targets',
      description: 'URIs mais visadas',
      color: 'orange',
    },
  ]

  const colorClasses = {
    violet: 'bg-violet-500/15 text-violet-400',
    amber: 'bg-amber-500/15 text-amber-400',
    red: 'bg-red-500/15 text-red-400',
    orange: 'bg-orange-500/15 text-orange-400',
  }

  const hoverColors = {
    violet: 'group-hover:text-violet-400',
    amber: 'group-hover:text-amber-400',
    red: 'group-hover:text-red-400',
    orange: 'group-hover:text-orange-400',
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      {links.map((link) => {
        const Icon = link.icon
        return (
          <Link
            key={link.href}
            href={link.href}
            className="quick-link group"
          >
            <div className="flex items-center gap-4 mb-3">
              <div className={`icon-wrapper ${colorClasses[link.color]}`}>
                <Icon className="w-5 h-5" />
              </div>
              <h3 className={`text-sm font-semibold text-white transition-colors ${hoverColors[link.color]}`}>
                {link.label}
              </h3>
            </div>
            <p className="text-xs text-zinc-500 leading-relaxed">{link.description}</p>
          </Link>
        )
      })}
    </div>
  )
}








