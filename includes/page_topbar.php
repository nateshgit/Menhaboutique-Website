<?php
/**
 * Reusable Page Top Bar component — back arrow + title + subtitle
 * Set before including:
 *   $pageTopbarTitle    (string, required)
 *   $pageTopbarSubtitle (string, optional)
 *   $pageTopbarBack     (string, optional URL — defaults to history.back())
 */
$_topTitle    = isset($pageTopbarTitle)    ? $pageTopbarTitle    : '';
$_topSubtitle = isset($pageTopbarSubtitle) ? $pageTopbarSubtitle : '';
$_topBack     = isset($pageTopbarBack)     ? $pageTopbarBack     : 'index.php';
// Strip legacy javascript:history.back() — we now handle it via JS onclick
if ($_topBack === 'javascript:history.back()') $_topBack = 'index.php';
?>
<div class="page-topbar">
    <div class="container page-topbar-inner">
        <a href="<?php echo htmlspecialchars($_topBack); ?>" class="page-topbar-back" aria-label="Go back"
           onclick="if(history.length>1){event.preventDefault();history.back();}">
            <i data-lucide="arrow-left"></i>
        </a>
        <div class="page-topbar-info">
            <h1 class="page-topbar-title"><?php echo $_topTitle; ?></h1>
            <?php if ($_topSubtitle): ?>
                <p class="page-topbar-subtitle"><?php echo $_topSubtitle; ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
