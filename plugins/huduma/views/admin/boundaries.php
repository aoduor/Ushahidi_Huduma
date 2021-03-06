<?php
/**
 * Boundary view page.
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Huduma Controller
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */
?>
			<div class="bg">
				<h2>
					<?php navigator::subtabs("boundaries"); ?>
				</h2>

				<!-- tabs -->
				<div class="tabs">
					<!-- tabset -->
					<a name="add"></a>
					<ul class="tabset">
						<li><a href="#" class="active" onclick="show_addedit(true)"><?php echo Kohana::lang('ui_main.add_edit'); ?></a></li>
					</ul>

					<!-- tab -->
					<div class="tab" id="addedit" style="display:none;">
						<?php print form::open(NULL,array('enctype' => 'multipart/form-data', 'id' => 'boundaryMain', 'name' => 'boundaryMain')); ?>
							<input type="hidden" id="boundary_id" name="boundary_id" value="<?php echo $form['boundary_id']; ?>" />
							<input type="hidden" name="action" id="action" value="a" />
							<div class="tab_form_item">
								<strong><?php echo Kohana::lang('ui_huduma.boundary_name');?></strong><br />
								<?php print form::input('boundary_name', $form['boundary_name'], ' class="text long2"'); ?>
							</div>
							<div class="tab_form_item">
								<strong><?php echo Kohana::lang('ui_huduma.boundary_type');?></strong><br />
								<span class="my-sel-holder"><?php print form::dropdown('boundary_type', $boundary_types, $form['boundary_type']); ?></span>
							</div>
							<div class="tab_form_item">
								<strong><?php echo Kohana::lang('ui_huduma.parent_boundary');?></strong><br />
								<span class="my-sel-holder"><?php print form::dropdown('parent_id', $parent_boundaries, $form['parent_id']); ?></span>
							</div>
							<div class="tab_form_item">
								<strong><?php echo Kohana::lang('ui_main.color'); ?></strong><br />
								<?php print form::input('boundary_color', $form['boundary_color'], ' class="text"'); ?>
								<script type="text/javascript" charset="utf-8">
									$(document).ready(function() {
										$('#boundary_color').ColorPicker({
											onSubmit: function(hsb, hex, rgb) {
												$('#boundary_color').val(hex);
											},
											onChange: function(hsb, hex, rgb) {
												$('#boundary_color').val(hex);
											},
											onBeforeShow: function () {
												$(this).ColorPickerSetColor(this.value);
											}
										})
										.bind('keyup', function(){
											$(this).ColorPickerSetColor(this.value);
										});
									});
								</script>
							</div>
							<div style="clear: both"></div>
							<div class="tab_form_item">
								<strong><?php echo Kohana::lang('ui_huduma.upload_geojson_file');?>:</strong><br />
								<?php
									print form::upload('boundary_layer_file', '', '');
								?>
							</div>
							<div style="clear: both"></div>
							<div class="tab_form_item">
								&nbsp;<br />
								<input type="image" src="<?php echo url::base() ?>media/img/admin/btn-save.gif" class="save-rep-btn" value="SAVE" />
							</div>
						<?php print form::close(); ?>
					</div>
				</div>

                <?php if ($form_error): ?>
                <!-- red-box -->
                <div class="red-box">
                    <h3><?php echo Kohana::lang('ui_main.error'); ?></h3>
                    <ul>
                    <?php
                    foreach ($errors as $error_item => $error_description)
                    {
                        print (!$error_description) ? '' : "<li>" . $error_description . "</li>";
                    }
                    ?>
                    </ul>
                </div>
                <?php endif; ?>

				<?php if ($form_saved): ?>
				<!-- green-box -->
				<div class="green-box">
					<h3><?php echo Kohana::lang('ui_huduma.boundary');?> <?php echo $form_action; ?></h3>
				</div>
				<?php endif; ?>
                
				<!-- report-table -->
				<div class="report-form">
					<?php print form::open(NULL,array('id' => 'boundaryListing','name' => 'boundaryListing')); ?>
						<input type="hidden" name="action" id="action" value="">
						<input type="hidden" name="boundary_id[]" id="boundary_id_single" value="">

						<div class="table-holder">
							<table class="table">
								<thead>
									<tr>
										<th class="col-1"></th>
										<th class="col-2"><?php echo Kohana::lang('ui_main.name'); ?></th>
										<th class="col-3"><?php echo Kohana::lang('ui_huduma.boundary_type'); ?></th>
										<th class="col-4"><?php echo Kohana::lang('ui_admin.actions'); ?></th>
									</tr>
								</thead>
								<tfoot>
									<tr class="foot">
										<td colspan="4"><?php echo $pagination; ?></td>
									</tr>
								</tfoot>
								<tbody>
								
								<?php if ($total_items == 0): ?>
								<tr>
									<td colspan="4" class="col"><h3><?php echo Kohana::lang('ui_main.no_results');?></h3></td>
								</tr>
								<?php endif; ?>
								
								<?php foreach ($boundaries as $boundary): ?>
								<tr>
									<td class="col-1"></td>
									<td class="col-2"><?php echo $boundary->boundary_name; ?></td>
									<td class="col-3">
										<?php 
											echo ($boundary->boundary_type == 1) ? Kohana::lang('ui_huduma.county') : Kohana::lang('ui_huduma.constituency');
										?>
									</td>
									<td class="col-4">
										<ul>
											<li class="none-separator">
												<a href="#add" onClick="fillFields('<?php echo(rawurlencode($boundary->id)); ?>',
												'<?php echo (rawurlencode($boundary->boundary_name)); ?>',
												'<?php echo (rawurlencode($boundary->boundary_type)); ?>',
												'<?php echo (rawurlencode($boundary->parent_id)); ?>',
												'<?php echo rawurlencode($boundary->boundary_color); ?>')">
												<?php echo Kohana::lang('ui_main.edit'); ?>
												</a>
											</li>
											<li>
												<a href="javascript:boundaryAction('d','DELETE','<?php echo(rawurlencode($boundary->id)); ?>')"
												class="del"><?php echo Kohana::lang('ui_main.delete'); ?></a>
											</li>
										</ul>
									</td>
								</tr>
								<?php endforeach; ?>
								
								</tbody>
							</table>
						</div>
					<?php print form::close(); ?>
				</div>

			</div>