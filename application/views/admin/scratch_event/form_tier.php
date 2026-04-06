<div class="page-header">
    <h1><i class='bx bxs-magic-wand'></i> <?= isset($tier) ? 'Edit' : 'Tambah'; ?> Tingkatan Event</h1>
    <a href="<?= base_url('admin/scratch_event'); ?>" class="btn btn-secondary"><i class='bx bx-arrow-back'></i> Kembali ke Event</a>
</div>

<div class="content-card">
    <form action="" method="POST">
        
        <div class="form-group">
            <label for="title">Judul Tier (Cth: Gosokan Silver, Gosokan Gold)</label>
            <input type="text" name="title" id="title" class="form-control" value="<?= isset($tier) ? html_escape($tier->title) : ''; ?>" required>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="min_amount">Minimal Belanja (Rp)</label>
                <input type="number" name="min_amount" id="min_amount" class="form-control" value="<?= isset($tier) ? $tier->min_amount : ''; ?>" required>
            </div>
            <div class="form-group">
                <label for="max_amount">Maksimal Belanja (Rp)</label>
                <input type="number" name="max_amount" id="max_amount" class="form-control" value="<?= isset($tier) ? $tier->max_amount : ''; ?>" placeholder="Kosongkan untuk 'ke atas'">
                <small>Kosongkan jika ini adalah tingkatan tertinggi (misal: 500rb+).</small>
            </div>
        </div>

        <div class="form-group">
            <label for="reward_ids">Pool Hadiah (Pilih dari Bank Hadiah)</label>
            <select name="reward_ids[]" id="reward_ids" multiple>
                <option value="">Pilih hadiah...</option>
                <?php 
                    $assigned_ids = $assigned_reward_ids ?? [];
                    foreach($all_rewards as $reward): 
                ?>
                    <option value="<?= $reward->id; ?>" <?= in_array($reward->id, $assigned_ids) ? 'selected' : ''; ?>>
                        [<?= ucfirst($reward->reward_type); ?>] <?= html_escape($reward->display_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Pilih satu atau lebih hadiah yang akan diacak untuk pemenang di tier ini.</small>
        </div>
        
        <div class="form-group">
            <label class="toggle-switch-label" for="is_active_checkbox">Status Tier</label>
            <label class="toggle-switch">
                <input type="checkbox" id="is_active_checkbox" name="is_active" value="1" <?= (isset($tier) && $tier->is_active == 1) ? 'checked' : (!isset($tier) ? 'checked' : ''); ?>>
                <span class="slider"></span>
            </label>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><i class='bx bxs-save'></i> Simpan Tingkatan</button>
        </div>

    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('reward_ids')) {
            new TomSelect('#reward_ids', {
                plugins: ['remove_button'],
                placeholder: 'Cari dan pilih hadiah...'
            });
        }
    });
</script>