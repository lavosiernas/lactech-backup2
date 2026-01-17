export type ThreatType = 
  | 'sql_injection'
  | 'xss'
  | 'command_injection'
  | 'path_traversal'
  | 'rce_php'
  | 'other';

export type Severity = 'low' | 'medium' | 'high' | 'critical';

export interface Threat {
  id: number;
  type: ThreatType;
  severity: Severity;
  ip: string;
  uri: string;
  timestamp: string;
  pattern?: string;
  user_agent?: string;
  country_code?: string;
  site_id?: number;
}

export interface ThreatStats {
  total_threats: number;
  by_type: Record<ThreatType, number>;
  by_severity: Record<Severity, number>;
  top_endpoints: Array<{
    endpoint: string;
    count: number;
  }>;
  top_ips: Array<{
    ip: string;
    count: number;
  }>;
}

export interface ThreatTimeline {
  hour: string;
  threats: number;
  by_type: Record<ThreatType, number>;
}

export interface SecurityEvent {
  id: number;
  type: 'threat' | 'alert' | 'block';
  threat?: Threat;
  message: string;
  timestamp: string;
  severity: Severity;
}

export interface RealTimeAlert {
  id: string;
  type: ThreatType;
  severity: Severity;
  ip: string;
  endpoint: string;
  message: string;
  timestamp: string;
}

