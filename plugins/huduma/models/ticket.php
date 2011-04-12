<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Model for Ticket
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author     Ushahidi Team <team@ushahidi.com> 
 * @package    Ushahidi - http://source.ushahididev.com
 * @module     Ticket Model
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license    http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */
class Ticket_Model extends ORM {

    // Relationships
    protected $belongs_to = array('agency_staff');
    
    protected $has_many = array('ticket_history');
    
    // Database table name
    protected $table_name = 'ticket';

}