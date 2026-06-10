<?php
/**
 * Partial: flash messages (errors, success, info).
 *
 * Variables are provided by the layout via Flash::consume() (single-use:
 * read and then removed from the session). The styling is defined in styles.css
 * (classes .flash / .flash--error / .flash--success / .flash--info) — no
 * inline styles, to maintain a strict CSP (style-src 'self').
 * Messages are wrapped in a .container so they align with the page content
 * instead of sticking to the viewport edges.
 *
 * @var array<int, string> $errors  Error messages
 * @var string|null        $success Success message
 * @var string|null        $info    Information message
 */
?>
<?php if (!empty($errors) || !empty($success) || !empty($info)): ?>
    <div class="container">
        <?php if (!empty($errors)): ?>
            <div class="flash flash--error" role="alert">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <output class="flash flash--success"><?= e($success) ?></output>
        <?php endif; ?>

        <?php if (!empty($info)): ?>
            <output class="flash flash--info"><?= e($info) ?></output>
        <?php endif; ?>
    </div>
<?php endif; ?>
