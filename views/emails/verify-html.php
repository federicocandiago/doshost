<?php /** @var string $name @var string $link @var string $app */ ?>
<!doctype html>
<html lang="<?= e(locale()) ?>">
<body style="margin:0;padding:32px 16px;background:#f4f4f4;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#111">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#000;border-radius:20px">
            <tr><td style="padding:32px 32px 8px;color:#fff;font-size:22px;font-weight:800;letter-spacing:-.02em"><?= e($app) ?></td></tr>
            <tr><td style="padding:8px 32px;color:#ddd;font-size:15px;line-height:1.6">
                <p style="margin:0 0 12px"><?= e(__('Hi :name,', ['name' => $name])) ?></p>
                <p style="margin:0 0 24px"><?= e(__('Thanks for signing up to :app! Open this link to confirm your email address and start using your DOS machines:', ['app' => $app])) ?></p>
            </td></tr>
            <tr><td style="padding:0 32px 24px">
                <a href="<?= e($link) ?>" style="display:inline-block;background:#fff;color:#000;text-decoration:none;font-weight:600;padding:12px 24px;border-radius:999px"><?= e(__('Confirm my email')) ?></a>
            </td></tr>
            <tr><td style="padding:0 32px 32px;color:#888;font-size:12px;line-height:1.6">
                <p style="margin:0 0 8px"><?= e(__('If the button doesn\'t work, copy this address into your browser:')) ?><br><span style="color:#bbb;word-break:break-all"><?= e($link) ?></span></p>
                <p style="margin:0"><?= e(__('If you didn\'t create an account, just ignore this email.')) ?></p>
            </td></tr>
        </table>
    </td></tr></table>
</body>
</html>
