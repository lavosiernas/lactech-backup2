<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Relatório</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      color: #333;
    }
    .header {
      text-align: center;
      margin-bottom: 20px;
      border-bottom: 2px solid #2a7f2a;
      padding-bottom: 10px;
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .logo {
      max-width: 150px;
      max-height: 100px;
      margin-bottom: 10px;
    }
    .header h1 {
      color: #2a7f2a;
      margin: 0;
      font-size: 24px;
    }
    .subheader {
      font-size: 14px;
      color: #666;
      margin-top: 5px;
    }
    .section-header {
      background-color: #2a7f2a;
      color: white;
      padding: 8px;
      font-size: 16px;
      margin: 20px 0 10px 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    th, td {
      border: 1px solid #2a7f2a;
      padding: 8px;
      text-align: left;
    }
    th {
      background-color: #2a7f2a;
      color: white;
      text-align: center;
    }
    tr:nth-child(even) {
      background-color: #f9f9f9;
    }
    .summary {
      margin: 20px 0;
      padding: 10px;
      background-color: #f0f0f0;
      border-left: 4px solid #2a7f2a;
    }
    .footer {
      text-align: center;
      font-size: 12px;
      color: #666;
      margin-top: 30px;
      border-top: 1px solid #ccc;
      padding-top: 10px;
    }
    .status-paid {
      color: #2a7f2a;
      font-weight: bold;
    }
    .status-pending {
      color: #d97706;
      font-weight: bold;
    }
    .status-canceled {
      color: #dc2626;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="header">
    {{#if logoUrl}}
    <img src="{{logoUrl}}" alt="Logo da Fazenda" class="logo">
    {{/if}}
    <h1>{{farmName}} - {{reportTitle}}</h1>
    <div class="subheader">Relatório gerado em: {{generatedDate}}</div>
  </div>
  
  <div id="content">
    {{content}}
  </div>
  
  <div class="footer">
    {{footerText}}
  </div>
</body>
</html>