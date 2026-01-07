<?php
/**
 * SafeNode - Theme Helper
 * Função helper para incluir tema em páginas
 */

function includeThemeAssets() {
    ?>
    <link rel="stylesheet" href="<?php echo getSafeNodeUrl('includes/theme-styles.css'); ?>">
    <script src="<?php echo getSafeNodeUrl('includes/theme-toggle.js'); ?>"></script>
    <?php
}

function renderThemeToggle() {
    ?>
    <button onclick="SafeNodeTheme.toggle(); if(typeof lucide !== 'undefined') lucide.createIcons();" class="theme-toggle" title="Alternar tema">
        <i data-lucide="sun" class="w-5 h-5 theme-toggle-light-icon"></i>
        <i data-lucide="moon" class="w-5 h-5 theme-toggle-dark-icon"></i>
    </button>
    <?php
}

