<?php
/**
 * SafeNode - Skeleton Loader Functions
 * Funções para gerar CSS e HTML de skeleton loaders
 */

if (!function_exists('skeletonLoaderCSS')) {
    function skeletonLoaderCSS() {
        return '
        <style>
            .skeleton-card, .skeleton-item {
                background-color: #0a0a0a; /* var(--bg-card) */
                border: 1px solid rgba(255,255,255,0.04); /* var(--border-subtle) */
                border-radius: 12px;
                overflow: hidden;
                position: relative;
            }
            .skeleton-line, .skeleton-circle, .skeleton-rectangle {
                background-color: rgba(255,255,255,0.08); /* var(--border-light) */
                border-radius: 4px;
                position: relative;
                overflow: hidden;
            }
            .skeleton-circle {
                border-radius: 50%;
            }
            .skeleton-line::after, .skeleton-circle::after, .skeleton-rectangle::after, .skeleton-glow::after {
                content: "";
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(255,255,255,0.05), transparent);
                animation: shimmer 1.5s infinite;
            }
            .skeleton-glow {
                animation: skeleton-glow 1.5s infinite;
            }
            @keyframes shimmer {
                0% { transform: translateX(-100%); }
                100% { transform: translateX(100%); }
            }
            @keyframes skeleton-glow {
                0%, 100% { opacity: 0.8; }
                50% { opacity: 0.5; }
            }
            .skeleton-fade-in {
                animation: fadeIn 0.3s ease-out forwards;
            }
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            /* Specific styles for dashboard to match existing cards */
            .stat-card .skeleton-line, .stat-card .skeleton-circle {
                background-color: rgba(255,255,255,0.08);
            }
            .chart-card .skeleton-line, .chart-card .skeleton-circle, .chart-card .skeleton-rectangle {
                background-color: rgba(255,255,255,0.08);
            }
            .table-card .skeleton-line {
                background-color: rgba(255,255,255,0.08);
            }
            .event-item .skeleton-line, .event-item .skeleton-circle {
                background-color: rgba(255,255,255,0.08);
            }
            /* Ensure x-cloak hides elements before Alpine.js initializes */
            [x-cloak] { display: none !important; }
        </style>
        ';
    }
}

if (!function_exists('skeletonLoaderHTML')) {
    function skeletonLoaderHTML($type = 'card', $options = []) {
        $html = '';
        
        switch ($type) {
            case 'card':
                $width = $options['width'] ?? '100%';
                $height = $options['height'] ?? '200px';
                $html = '<div class="skeleton-card skeleton-fade-in" style="width: ' . htmlspecialchars($width) . '; height: ' . htmlspecialchars($height) . ';">
                    <div class="skeleton-line" style="width: 60%; height: 20px; margin: 16px;"></div>
                    <div class="skeleton-line" style="width: 80%; height: 16px; margin: 8px 16px;"></div>
                    <div class="skeleton-line" style="width: 40%; height: 16px; margin: 8px 16px;"></div>
                </div>';
                break;
                
            case 'line':
                $width = $options['width'] ?? '100%';
                $height = $options['height'] ?? '16px';
                $html = '<div class="skeleton-line skeleton-fade-in" style="width: ' . htmlspecialchars($width) . '; height: ' . htmlspecialchars($height) . ';"></div>';
                break;
                
            case 'circle':
                $size = $options['size'] ?? '40px';
                $html = '<div class="skeleton-circle skeleton-fade-in" style="width: ' . htmlspecialchars($size) . '; height: ' . htmlspecialchars($size) . ';"></div>';
                break;
                
            case 'rectangle':
                $width = $options['width'] ?? '100%';
                $height = $options['height'] ?? '100px';
                $html = '<div class="skeleton-rectangle skeleton-fade-in" style="width: ' . htmlspecialchars($width) . '; height: ' . htmlspecialchars($height) . ';"></div>';
                break;
                
            default:
                $html = '<div class="skeleton-card skeleton-fade-in">
                    <div class="skeleton-line" style="width: 60%; height: 20px; margin: 16px;"></div>
                    <div class="skeleton-line" style="width: 80%; height: 16px; margin: 8px 16px;"></div>
                </div>';
        }
        
        return $html;
    }
}
?>















