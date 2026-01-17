import axios from 'axios';

const API_BASE_URL = '/api';

const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

export interface ThreatDetectionResponse {
  success: boolean;
  timestamp: number;
  timeframe: string;
  data: {
    threats: Array<{
      id: number;
      type: string;
      severity: string;
      ip: string;
      uri: string;
      timestamp: string;
      pattern?: string;
    }>;
    stats: {
      total_threats: number;
      by_type: Record<string, number>;
      by_severity: Record<string, number>;
      top_endpoints: Array<{ endpoint: string; count: number }>;
      top_ips: Array<{ ip: string; count: number }>;
    };
    timeline: Array<{
      hour: string;
      threats: number;
      by_type: Record<string, number>;
    }>;
  };
}

export const securityApi = {
  getThreats: async (timeframe: string = '24h'): Promise<ThreatDetectionResponse> => {
    const response = await apiClient.get<ThreatDetectionResponse>(
      `/threat-detection.php?timeframe=${timeframe}`
    );
    return response.data;
  },

  getRealTimeStats: async (): Promise<any> => {
    const response = await apiClient.get('/realtime-stats.php');
    return response.data;
  },
};

export default apiClient;

