"""
SafeNode Human Verification SDK - Python

SDK oficial do SafeNode para integração em aplicações Python

Versão: 1.0.0
Licença: MIT
"""

import requests
import time
import json
from typing import Optional, Dict, Any


class SafeNodeHV:
    """Cliente SDK do SafeNode Human Verification para Python"""
    
    def __init__(self, api_base_url: str, api_key: str, options: Optional[Dict[str, Any]] = None):
        """
        Inicializa o cliente SDK
        
        Args:
            api_base_url: URL base da API (ex: https://safenode.cloud/api/sdk)
            api_key: Chave de API do SafeNode
            options: Opções adicionais (max_retries, retry_delay, token_max_age)
        """
        self.api_base_url = api_base_url.rstrip('/')
        self.api_key = api_key
        self.token = None
        self.nonce = None
        self.initialized = False
        self.max_retries = options.get('max_retries', 3) if options else 3
        self.retry_delay = options.get('retry_delay', 1000) if options else 1000  # ms
        self.token_max_age = options.get('token_max_age', 3600) if options else 3600  # segundos
        self.init_time = None
    
    def init(self, retry_count: int = 0) -> bool:
        """
        Inicializa o SDK e obtém o token de verificação
        
        Args:
            retry_count: Contador interno para retry
            
        Returns:
            True se inicializado com sucesso
            
        Raises:
            Exception: Em caso de erro
        """
        if not self.api_key:
            raise Exception('API key é obrigatória')
        
        try:
            url = f"{self.api_base_url}/init.php"
            params = {'api_key': self.api_key}
            headers = {
                'Accept': 'application/json',
                'X-API-Key': self.api_key
            }
            
            response = requests.get(url, params=params, headers=headers, timeout=10)
            
            if response.status_code == 429:
                raise Exception('Rate limit excedido. Tente novamente em alguns instantes.')
            
            response.raise_for_status()
            data = response.json()
            
            if data.get('success') and data.get('token'):
                self.token = data['token']
                self.nonce = data.get('nonce', '')
                self.token_max_age = data.get('max_age', 3600)
                self.init_time = time.time()
                self.initialized = True
                
                return True
            else:
                raise Exception('Token não recebido')
                
        except requests.RequestException as e:
            # Retry automático em caso de erro de rede
            if retry_count < self.max_retries:
                time.sleep(self.retry_delay / 1000 * (retry_count + 1))
                return self.init(retry_count + 1)
            
            self.initialized = False
            raise Exception(f'Erro ao inicializar: {str(e)}')
    
    def _is_token_valid(self) -> bool:
        """Verifica se o token ainda é válido"""
        if not self.init_time or not self.token:
            return False
        
        age = time.time() - self.init_time
        return age < self.token_max_age
    
    def validate(self, retry_count: int = 0) -> Dict[str, Any]:
        """
        Valida a verificação humana
        
        Args:
            retry_count: Contador interno para retry
            
        Returns:
            Dicionário com resultado da validação
            
        Raises:
            Exception: Em caso de erro
        """
        if not self.initialized or not self.token:
            if self.token and not self._is_token_valid():
                self.init()
            else:
                raise Exception('SDK não inicializado. Chame init() primeiro.')
        
        if not self._is_token_valid():
            self.init()
        
        try:
            payload = {
                'token': self.token,
                'nonce': self.nonce or '',
                'js_enabled': '1',
                'api_key': self.api_key
            }
            
            headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-API-Key': self.api_key
            }
            
            url = f"{self.api_base_url}/validate.php"
            response = requests.post(url, json=payload, headers=headers, timeout=10)
            
            if response.status_code == 200:
                data = response.json()
                if data.get('success'):
                    return {
                        'valid': True,
                        'message': data.get('message', 'Verificação válida')
                    }
                else:
                    raise Exception(data.get('error', 'Validação falhou'))
            else:
                data = response.json() if response.text else {}
                raise Exception(data.get('error', f'Erro HTTP {response.status_code}'))
                
        except requests.RequestException as e:
            if retry_count < self.max_retries:
                time.sleep(self.retry_delay / 1000 * (retry_count + 1))
                return self.validate(retry_count + 1)
            
            raise Exception(f'Erro ao validar: {str(e)}')
    
    def is_initialized(self) -> bool:
        """Verifica se o SDK está inicializado"""
        return self.initialized
    
    def get_token(self) -> Optional[str]:
        """Obtém o token atual"""
        return self.token


