<?php
/**
- app/Views/dashboard/greeting.php (Senior-Friendly Accessibility Version)
 *
- Needs: $label
- Optional: employee_name, employee_nickname, employee_photo, employee_position, salutation,
-           is_birthday, work_anniversary, tenure_text, supervisor_name
 */
$maxMeta = 3;

// ---- greeting ----
$hour = (int) date('H');
if ($hour >= 5 && $hour < 11)      { $greeting = 'Selamat pagi'; }
elseif ($hour >= 11 && $hour < 15) { $greeting = 'Selamat siang'; }
elseif ($hour >= 15 && $hour < 18) { $greeting = 'Selamat sore'; }
else                               { $greeting = 'Selamat malam'; }

// ---- who ----
$full  = trim((string) ($employee_name ?? ''));
$first = trim((string) ($employee_nickname ?? ''));
if ($first === '' && $full !== '') { $first = explode(' ', $full)[0]; }
$salut = trim((string) ($salutation ?? ''));

$words    = preg_split('/\s+/', $full, -1, PREG_SPLIT_NO_EMPTY) ?: [];
$initials = mb_strtoupper(mb_substr($words[0] ?? '?', 0, 1) . (isset($words[1]) ? mb_substr($words[1], 0, 1) : ''));
$pairs    = [['#1d4ed8', '#1e40af'], ['#059669', '#064e3b'], ['#d97706', '#78350f'], ['#7c3aed', '#4c1d95'], ['#db2777', '#831843'], ['#0891b2', '#164e63']];
$pair     = $pairs[crc32($full) % count($pairs)];

// ---- celebration ----
$note = null;
if (! empty($is_birthday)) {
    $note = ['bday', '🎂', 'Selamat ulang tahun' . ($first !== '' ? ', ' . $first : '') . '! Semoga hari ini menyenangkan.'];
} elseif (! empty($work_anniversary)) {
    $note = ['anniv', '🎉', (int) $work_anniversary . ' tahun bersama kami. Terima kasih atas dedikasinya!'];
}

// ---- quiet meta line ----
$meta = [];
if (! empty($employee_position)) { $meta[] = ['far fa-id-badge',      esc($employee_position)]; }
if (! empty($supervisor_name))   { $meta[] = ['far fa-user',          'Atasan: ' . esc($supervisor_name)]; }
if (! empty($tenure_text))       { $meta[] = ['far fa-calendar-check', 'Bergabung ' . esc($tenure_text)]; }
$meta = array_slice($meta, 0, $maxMeta);
?>
<div class="gr">

    <div class="gr-avatar-wrap">
        <?php if (! empty($employee_photo)): ?>
            <img class="gr-avatar" src="<?= esc($employee_photo, 'attr') ?>" alt="" width="68" height="68">
        <?php else: ?>
            <span class="gr-avatar gr-initials" style="background:linear-gradient(135deg,<?= $pair[0] ?>,<?= $pair[1] ?>)" aria-hidden="true"><?= esc($initials) ?></span>
        <?php endif; ?>
        <?php if (! empty($is_birthday)): ?><span class="gr-badge" aria-hidden="true">🎂</span><?php endif; ?>
    </div>

    <div class="gr-body">
        <h1 class="gr-title"><?= $greeting ?><?php if ($first !== ''): ?>, <?= $salut !== '' ? esc($salut) . ' ' : '' ?><em><?= esc($first) ?></em><?php endif; ?></h1>

        <?php if (! empty($label)): ?>
            <p class="gr-sub">Ringkasan aktivitas untuk Anda dan tim</p>
        <?php endif; ?>

        <?php if ($note): ?>
            <div class="gr-note gr-note--<?= $note[0] ?>"><span aria-hidden="true"><?= $note[1] ?></span> <?= esc($note[2]) ?></div>
        <?php endif; ?>

        <?php if ($meta): ?>
            <div class="gr-meta">
                <?php foreach ($meta as $i => [$icon, $text]): ?>
                    <span class="<?= $i > 0 ? 'gr-opt' : '' ?>"><i class="<?= $icon ?>" aria-hidden="true"></i><?= $text ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.gr {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  min-width: 0;
  --gr-ink: #0f172a;
  --gr-mute: #334155;
  --gr-soft: #475569;
  --gr-accent: #1d4ed8;
}

.gr-avatar-wrap {
  position: relative;
  flex: none;
}

.gr-avatar {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  object-fit: cover;
  display: flex;
  box-shadow: 0 0 0 3px #fff, 0 0 0 5px #cbd5e1;
}

.gr-initials {
  align-items: center;
  justify-content: center;
  color: #fff;
  font-weight: 800;
  font-size: 1.35rem;
  letter-spacing: .02em;
}

.gr-badge {
  position: absolute;
  right: -6px;
  bottom: -4px;
  font-size: 1.3rem;
  background: #fff;
  border-radius: 50%;
  line-height: 1;
  padding: 3px;
  box-shadow: 0 2px 6px rgba(15, 23, 42, .25);
  border: 1px solid #cbd5e1;
}

.gr-body {
  min-width: 0;
}

.gr-title {
  margin: 0 0 .25rem;
  font-size: 1.85rem;
  font-weight: 800;
  line-height: 1.25;
  color: var(--gr-ink);
  letter-spacing: -.01em;
}

.gr-title em {
  font-style: normal;
  color: var(--gr-accent);
  font-weight: 800;
}

.gr-sub {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: var(--gr-mute);
  line-height: 1.4;
}

.gr-note {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  margin-top: .65rem;
  padding: .5rem 1rem;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.4;
}

.gr-note--bday {
  background: #fef2f2;
  color: #991b1b;
  border: 2px solid #fca5a5;
}

.gr-note--anniv {
  background: #f3e8ff;
  color: #4c1d95;
  border: 2px solid #c084fc;
}

.gr-meta {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: .5rem 1.25rem;
  margin-top: .65rem;
  font-size: .95rem;
  font-weight: 600;
  color: var(--gr-mute);
}

.gr-meta > span {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  color: #1e293b;
}

.gr-meta > span i {
  color: var(--gr-soft);
  font-size: 1rem;
}

@media (max-width: 575.98px) {
  .gr { gap: .85rem; }
  .gr-avatar { width: 52px; height: 52px; }
  .gr-initials { font-size: 1.05rem; }
  .gr-title { font-size: 1.45rem; }
  .gr-sub { font-size: .9rem; }
  .gr-meta { font-size: .88rem; }
  .gr-opt { display: none !important; }
}
</style>