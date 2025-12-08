#!/usr/bin/env python3
"""
Exemplo de uso do SDK Python do SafeNode
"""

from safenode_hv import SafeNodeHV
from flask import Flask, request, jsonify

app = Flask(__name__)
safenode = SafeNodeHV('https://safenode.cloud/api/sdk', 'sua-api-key-aqui')

@app.route('/')
def index():
    """Inicializar SDK na página"""
    try:
        safenode.init()
        return jsonify({
            'success': True,
            'message': 'SDK inicializado',
            'token': safenode.get_token()[:16] + '...' if safenode.get_token() else None
        })
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/submit', methods=['POST'])
def submit_form():
    """Validar e processar formulário"""
    try:
        result = safenode.validate()
        
        if result['valid']:
            # Processar formulário com segurança
            return jsonify({
                'success': True,
                'message': 'Formulário validado e processado com sucesso!'
            })
        else:
            return jsonify({
                'success': False,
                'error': result.get('message', 'Validação falhou')
            }), 400
            
    except Exception as e:
        return jsonify({'error': str(e)}), 400

if __name__ == '__main__':
    app.run(debug=True)




