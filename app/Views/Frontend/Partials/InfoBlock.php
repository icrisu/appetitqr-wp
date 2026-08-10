<?php
namespace AppetitQR\Views\Frontend\Partials;
if ( ! defined( 'ABSPATH' ) ) exit;

use AppetitQR\Helpers\ImageHelper;
use AppetitQR\Helpers\OpeningHoursHelper;
use AppetitQR\Helpers\TranslationHelper;
use AppetitQR\Services\LabelService;
use AppetitQR\Utils\Sanitizer;

class InfoBlock {

    /** Templates that lead with a landscape cover photo. Bistro deliberately does not. */
    const COVER_TEMPLATES = ['default', 'modern', 'nocturnal'];

    /**
     * The menu's opening header, matching whichever template the account uses.
     *
     * Three of the four templates lead with a full-width landscape cover photo carrying
     * the name over it; Bistro instead uses a typographic header with no image
     * (bistro/MenuClient.tsx:244). A hero template with no cover image uploaded falls
     * back to a compact row rather than rendering an empty scrim over nothing.
     */
    static function renderHeader(array $location, array $widths, string $lang, string $template = 'default'): void {
        $name  = $location['name'] ?? '';
        $logo  = $location['logoImage'] ?? null;
        $cover = $location['coverImage'] ?? null;

        if ($name === '' && !$logo && !$cover) {
            return;
        }

        if ($template === 'bistro') {
            self::renderTypographicHeader($location, $name);
            return;
        }

        if ($cover && in_array($template, self::COVER_TEMPLATES, true)) {
            self::renderCoverHeader($location, $widths, $name, $logo, $cover);
            return;
        }

        self::renderCompactHeader($location, $widths, $name, $logo);
    }

    /**
     * Landscape cover with a scrim and centred name — the diner-facing hero.
     */
    private static function renderCoverHeader(array $location, array $widths, string $name, ?string $logo, string $cover): void {
        ?>
        <header class="apq-hero">
            <img
                class="apq-hero-image"
                <?php ImageHelper::printImageAttrs($cover, $widths, '100vw'); ?>
                alt="<?php echo esc_attr($name); ?>"
            />
            <?php // Scrim: the name sits on an arbitrary photo, so it needs a guaranteed backdrop. ?>
            <div class="apq-hero-scrim"></div>
            <div class="apq-hero-content">
                <?php if ($logo): ?>
                    <img
                        class="apq-hero-logo"
                        <?php ImageHelper::printImageAttrs($logo, $widths, '80px'); ?>
                        alt="<?php echo esc_attr($name); ?>"
                    />
                <?php endif; ?>
                <h2 class="apq-hero-title"><?php echo esc_html($name); ?></h2>
                <?php if (!empty($location['subtitle'])): ?>
                    <p class="apq-hero-subtitle"><?php echo esc_html($location['subtitle']); ?></p>
                <?php endif; ?>
            </div>
        </header>
        <?php
    }

    /**
     * Bistro: centred name over a rule, no image.
     */
    private static function renderTypographicHeader(array $location, string $name): void {
        ?>
        <header class="apq-hero-type">
            <h2 class="apq-hero-title"><?php echo esc_html($name); ?></h2>
            <?php if (!empty($location['subtitle'])): ?>
                <p class="apq-hero-subtitle"><?php echo esc_html($location['subtitle']); ?></p>
            <?php endif; ?>
        </header>
        <?php
    }

    /**
     * Fallback for a hero template with no cover image uploaded.
     */
    private static function renderCompactHeader(array $location, array $widths, string $name, ?string $logo): void {
        ?>
        <header class="apq-header">
            <?php if ($logo): ?>
                <img
                    class="apq-header-logo"
                    <?php ImageHelper::printImageAttrs($logo, $widths, '96px'); ?>
                    alt="<?php echo esc_attr($name); ?>"
                />
            <?php endif; ?>
            <div class="apq-header-text">
                <h2 class="apq-header-title"><?php echo esc_html($name); ?></h2>
                <?php if (!empty($location['subtitle'])): ?>
                    <p class="apq-header-subtitle"><?php echo esc_html($location['subtitle']); ?></p>
                <?php endif; ?>
            </div>
        </header>
        <?php
    }

    /**
     * About / contact / hours, each gated on the location's own show* toggles so the
     * WordPress page never reveals something hidden on the diner-facing menu.
     */
    static function render(array $location, LabelService $labels, string $lang): void {
        $about = TranslationHelper::pick($location['translations'] ?? null, $lang, 'about');
        if ($about === '') {
            $about = is_string($location['about'] ?? null) ? $location['about'] : '';
        }

        $showPhone   = !empty($location['showPhoneNumber']) && !empty($location['phoneNumber']);
        $showAddress = !empty($location['showAddress']) && !empty($location['address']);
        $showHours   = !empty($location['showWorkingHours']);
        $schedule    = $showHours ? OpeningHoursHelper::getSchedule($location['workingHours'] ?? null) : [];
        $isOpen      = OpeningHoursHelper::isOpenNow($location['workingHours'] ?? null, $location['timezone'] ?? null);

        if ($about === '' && !$showPhone && !$showAddress && empty($schedule)) {
            return;
        }
        ?>
        <section class="apq-info">
            <?php if ($about !== ''): ?>
                <div class="apq-info-block">
                    <h3 class="apq-info-title"><?php echo esc_html($labels->get('about', __('About', 'sakura-pixel-menu-embed-for-appetitqr'))); ?></h3>
                    <div class="apq-info-about"><?php echo wp_kses_post(wpautop($about)); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($showAddress || $showPhone): ?>
                <div class="apq-info-block">
                    <?php if ($showAddress): ?>
                        <h3 class="apq-info-title"><?php echo esc_html($labels->get('address', __('Address', 'sakura-pixel-menu-embed-for-appetitqr'))); ?></h3>
                        <p class="apq-info-line"><?php echo esc_html($location['address']); ?></p>
                    <?php endif; ?>

                    <?php if ($showPhone): ?>
                        <h3 class="apq-info-title"><?php echo esc_html($labels->get('phone', __('Phone', 'sakura-pixel-menu-embed-for-appetitqr'))); ?></h3>
                        <p class="apq-info-line">
                            <a href="tel:<?php echo esc_attr(Sanitizer::sanitizePhone($location['phoneNumber'])); ?>">
                                <?php echo esc_html($location['phoneNumber']); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($schedule)): ?>
                <div class="apq-info-block">
                    <h3 class="apq-info-title">
                        <?php echo esc_html($labels->get('business_hours', __('Business Hours', 'sakura-pixel-menu-embed-for-appetitqr'))); ?>
                        <?php if ($isOpen !== null): ?>
                            <span class="apq-open-badge<?php echo $isOpen ? ' is-open' : ''; ?>">
                                <?php echo $isOpen ? esc_html__('Open now', 'sakura-pixel-menu-embed-for-appetitqr') : esc_html__('Closed', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
                            </span>
                        <?php endif; ?>
                    </h3>
                    <ul class="apq-hours">
                        <?php foreach ($schedule as $day): ?>
                            <li class="apq-hours-row">
                                <span class="apq-hours-day"><?php echo esc_html(OpeningHoursHelper::dayLabel($day['key'])); ?></span>
                                <span class="apq-hours-times">
                                    <?php if (!$day['enabled'] || empty($day['intervals'])): ?>
                                        <?php esc_html_e('Closed', 'sakura-pixel-menu-embed-for-appetitqr'); ?>
                                    <?php else: ?>
                                        <?php
                                        $parts = [];
                                        foreach ($day['intervals'] as $interval) {
                                            $from = $interval['from'] ?? '';
                                            $to   = $interval['to'] ?? '';
                                            if ($from !== '' && $to !== '') {
                                                $parts[] = $from . ' – ' . $to;
                                            }
                                        }
                                        echo esc_html(implode(', ', $parts));
                                        ?>
                                    <?php endif; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }
}
