<?php
require_once __DIR__ . '/../src/SettingsHelper.php';

$notice = SettingsHelper::getNoticeBar();
?>

<?php if ($notice['enabled']): ?>
<div class="notice-bar alert alert-<?= htmlspecialchars($notice['type']) ?> alert-dismissible fade show mb-0" role="alert">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-10">
                <div class="d-flex align-items-center">
                    <?php
                    $icon = '';
                    switch ($notice['type']) {
                        case 'success':
                            $icon = 'fas fa-check-circle';
                            break;
                        case 'warning':
                            $icon = 'fas fa-exclamation-triangle';
                            break;
                        case 'danger':
                            $icon = 'fas fa-exclamation-circle';
                            break;
                        default:
                            $icon = 'fas fa-info-circle';
                            break;
                    }
                    ?>
                    <i class="<?= $icon ?> me-2"></i>
                    <span class="notice-text"><?= htmlspecialchars($notice['text']) ?></span>
                </div>
            </div>
            <div class="col-md-2 text-end">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>

<style>
.notice-bar {
    border-radius: 0;
    border: none;
    margin-bottom: 0;
    padding: 0.75rem 0;
    font-weight: 500;
}

.notice-bar .container {
    max-width: 100%;
}

.notice-text {
    font-size: 0.95rem;
    line-height: 1.4;
}

.notice-bar .btn-close {
    font-size: 0.8rem;
    opacity: 0.7;
}

.notice-bar .btn-close:hover {
    opacity: 1;
}

/* Animation for notice bar */
.notice-bar {
    animation: slideDown 0.5s ease-out;
}

@keyframes slideDown {
    from {
        transform: translateY(-100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .notice-bar {
        padding: 0.5rem 0;
    }
    
    .notice-text {
        font-size: 0.9rem;
    }
    
    .notice-bar .col-md-2 {
        margin-top: 0.5rem;
        text-align: center !important;
    }
}
</style>
<?php endif; ?>
