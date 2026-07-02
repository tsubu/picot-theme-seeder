<?php
if (! defined('ABSPATH')) {
    exit;
}

$picotse_basic_info_prefix  = isset($picotse_basic_info_prefix) ? $picotse_basic_info_prefix : 'cts';
$picotse_basic_info_variant = isset($picotse_basic_info_variant) ? $picotse_basic_info_variant : 'classic';
?>
<table class="form-table">
    <?php foreach (Picotse_Classic_Definitions::get_basic_info_fields($picotse_basic_info_variant) as $picotse_field_name => $picotse_field) : ?>
        <?php
        $picotse_input_id = $picotse_basic_info_prefix . '-' . $picotse_field_name;
        $picotse_type     = $picotse_field['type'] ?? 'text';
        ?>
        <tr>
            <th><label for="<?php echo esc_attr($picotse_input_id); ?>"><?php echo esc_html($picotse_field['label']); ?></label></th>
            <td>
                <?php if ('textarea' === $picotse_type) : ?>
                    <textarea
                        id="<?php echo esc_attr($picotse_input_id); ?>"
                        name="<?php echo esc_attr($picotse_field_name); ?>"
                        class="large-text"
                        rows="<?php echo esc_attr((string) ($picotse_field['rows'] ?? 3)); ?>"
                    ></textarea>
                <?php else : ?>
                    <input
                        type="<?php echo esc_attr($picotse_type); ?>"
                        id="<?php echo esc_attr($picotse_input_id); ?>"
                        name="<?php echo esc_attr($picotse_field_name); ?>"
                        class="regular-text"
                        <?php if (! empty($picotse_field['placeholder'])) : ?>
                            placeholder="<?php echo esc_attr($picotse_field['placeholder']); ?>"
                        <?php endif; ?>
                        <?php if (! empty($picotse_field['required'])) : ?>
                            required
                        <?php endif; ?>
                    >
                <?php endif; ?>
                <p class="description pts-field-desc"><?php echo esc_html($picotse_field['description']); ?></p>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
