<p style="margin:0;font-size:15px;line-height:1.65;color:#5b6475;"><?= esc($intro) ?></p>
<?php if ($details !== []): ?>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:24px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:16px;">
        <tr><td style="padding:20px 22px;font-size:14px;line-height:1.65;color:#334155;">
            <?php foreach ($details as $detailLabel => $value): ?>
                <div><strong><?= esc($detailLabel) ?>:</strong> <?= esc($value) ?></div>
            <?php endforeach; ?>
        </td></tr>
    </table>
<?php endif; ?>
<table role="presentation" cellspacing="0" cellpadding="0" style="margin-top:24px;">
    <tr><td bgcolor="#2563eb" style="border-radius:9px;">
        <a href="<?= esc($url, 'attr') ?>" style="display:inline-block;padding:12px 18px;border-radius:9px;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;"><?= esc($button) ?></a>
    </td></tr>
</table>
