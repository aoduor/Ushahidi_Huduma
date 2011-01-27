<?php
/**
 * Service providers js file.
 *
 * Handles javascript stuff related  to api log function
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com>
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     API Controller
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL)
 */
?>

<?php require SYSPATH.'../application/views/admin/form_utils_js.php' ?>

	// Ajax Submission
	function serviceProviderAction( action, confirmAction, service_provider_id )
	{
		var statusMessage;
		if( !isChecked( "service_provider" ) && service_provider_id =='' )
		{
			alert('Please select at least one api log.');
		} else {
			var answer = confirm('<?php echo Kohana::lang('ui_admin.are_you_sure_you_want_to'); ?> ' + confirmAction + '?')
			if (answer){
				// Set Submit Type
				$("#action").attr("value", action);

				if (service_provider_id != '')
				{
					// Submit Form For Single Item
					$("#service_provider_single").attr("value", service_provider_id);
					$("#serviceProviderMain").submit();
				}
				else
				{
					// Set Hidden form item to 000 so that it doesn't return server side error for blank value
					$("#api_log_single").attr("value", "000");

					// Submit Form For Multiple Items
					$("#serviceProviderMain").submit();
				}

			} else {
				return false;
			}
		}
	}