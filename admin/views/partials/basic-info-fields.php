<?php
if (! defined('ABSPATH')) {
    exit;
}

$pts_basic_info_prefix  = isset($pts_basic_info_prefix) ? $pts_basic_info_prefix : 'cts';
$pts_basic_info_variant = isset($pts_basic_info_variant) ? $pts_basic_info_variant : 'classic';
?>
<table class="form-table">
    <?php foreach (Picotse_Classic_Definitions::get_basic_info_fields($pts_basic_info_variant) as $name => $field) : ?>
        <?php
        $input_id = $pts_basic_info_prefix . '-' . $name;
        $type     = $field['type'] ?? 'text';
        ?>
        <tr>
            <th><label for="<?php echo esc_attr($input_id); ?>"><?php echo esc_html($field['label']); ?></label></th>
            <td>
                <?php if ('textarea' === $type) : ?>
                    <textarea
                        id="<?php echo esc_attr($input_id); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        class="large-text"
                        rows="<?php echo esc_attr((string) ($field['rows'] ?? 3)); ?>"
                    ></textarea>
                <?php else : ?>
                    <input
                        type="<?php echo esc_attr($type); ?>"
                        id="<?php echo esc_attr($input_id); ?>"
                        name="<?php echo esc_attr($name); ?>"
                        class="regular-text"
                        <?php if (! empty($field['placeholder'])) : ?>
                            placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                        <?php endif; ?>
                        <?php if (! empty($field['required'])) : ?>
                            required
                        <?php endif; ?>
                    >
                <?php endif; ?>
                <p class="description pts-field-desc"><?php echo esc_html($field['description']); ?></p>
            </td>
        </tr>
    <?php endforeach; ?>
</table>
