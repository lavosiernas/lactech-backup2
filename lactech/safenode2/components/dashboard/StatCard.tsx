'use client'

interface StatCardProps {
  label: string
  value: string
  change?: string
  changeType?: 'positive' | 'negative' | 'warning'
  subtitle?: string
}

export default function StatCard({ label, value, change, changeType = 'positive', subtitle }: StatCardProps) {
  const changeColors = {
    positive: 'text-white bg-white/10',
    negative: 'text-red-400 bg-red-500/10',
    warning: 'text-amber-400 bg-amber-500/10',
  }

  return (
    <div className="stat-card group">
      <div className="flex items-center justify-between mb-1">
        <p className="text-xs sm:text-sm font-medium text-zinc-400">{label}</p>
      </div>
      <div className="flex items-end justify-between mt-3 sm:mt-4">
        <p className="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight">{value}</p>
        {change && (
          <span className={`text-[10px] sm:text-xs font-semibold px-1.5 sm:px-2.5 py-0.5 sm:py-1 rounded-lg ${changeColors[changeType]}`}>
            {change}
          </span>
        )}
      </div>
      {subtitle && (
        <p className="text-[10px] sm:text-xs text-zinc-600 mt-2 sm:mt-3">{subtitle}</p>
      )}
    </div>
  )
}

