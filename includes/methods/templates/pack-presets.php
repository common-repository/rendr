<?php

	if (!defined('ABSPATH') || !defined('WPINC')) { exit;}

?>

<tr valign="top">
	<td colspan="2" style="padding: 0">
		<div class="rendr-presets">
			<table class="widefat">
				<thead>
					<tr>
						<th class="wcrendr-box-remove"></th>
						<th class="wcrendr-box-name"><?php esc_html_e('Label', 'rendr'); ?></th>
						<th class="wcrendr-box-width"><?php esc_html_e('Length (cm)', 'rendr'); ?></th>
						<th class="wcrendr-box-length"><?php esc_html_e('Width (cm)', 'rendr'); ?></th>
						<th class="wcrendr-box-height"><?php esc_html_e('Height (cm)', 'rendr'); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr class="clone">
						<td class="wcrendr-box-remove"><span class="dashicons dashicons-no-alt"></span></td>
						<td class="wcrendr-box-name">
							<input type="text" name="<?php echo esc_attr($field_key).'_clone[0][label]' ?>" value="" placeholder="<?php esc_attr_e('Box label', 'rendr'); ?>" />
						</td>
						<td class="wcrendr-box-length">
							<input type="number" name="<?php echo esc_attr($field_key).'_clone[0][length]' ?>" value="" placeholder="<?php esc_attr_e('Length in cm', 'rendr'); ?>" />
						</td>
						<td class="wcrendr-box-width">
							<input type="number" name="<?php echo esc_attr($field_key).'_clone[0][width]' ?>" value="" placeholder="<?php esc_attr_e('Width in cm', 'rendr'); ?>" />
						</td>
						<td class="wcrendr-box-height">
							<input type="number" name="<?php echo esc_attr($field_key).'_clone[0][height]' ?>" value="" placeholder="<?php esc_attr_e('Height in cm', 'rendr'); ?>" />
						</td>
					</tr>
					<?php foreach($presets as $index => $preset) : ?>
						<tr>
							<td class="wcrendr-box-remove"><span class="dashicons dashicons-no-alt"></span></td>
							<td class="wcrendr-box-name">
								<input type="text" name="<?php echo esc_attr($field_key).'['.esc_attr($index).'][label]'; ?>" value="<?php echo esc_attr($preset['label']) ?>" placeholder="<?php esc_attr_e('Box label', 'rendr'); ?>" />
							</td>
							<td class="wcrendr-box-length">
								<input type="number" name="<?php echo esc_attr($field_key).'['.esc_attr($index).'][length]'; ?>" value="<?php echo esc_attr($preset['length']) ?>" placeholder="<?php esc_attr_e('Length in cm', 'rendr'); ?>" />
							</td>
							<td class="wcrendr-box-width">
								<input type="number" name="<?php echo esc_attr($field_key).'['.esc_attr($index).'][width]'; ?>" value="<?php echo esc_attr($preset['width']) ?>" placeholder="<?php esc_attr_e('Width in cm', 'rendr'); ?>" />
							</td>
							<td class="wcrendr-box-height">
								<input type="number" name="<?php echo esc_attr($field_key).'['.esc_attr($index).'][height]'; ?>" value="<?php echo esc_attr($preset['height']) ?>" placeholder="<?php esc_attr_e('Height in cm', 'rendr'); ?>" />
							</td>
						</tr>
					<?php endforeach ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="5" style="text-align: right">
							<button type="button" class="button"><?php esc_html_e('Add Box', 'rendr'); ?></button>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
	</td>
</tr>
