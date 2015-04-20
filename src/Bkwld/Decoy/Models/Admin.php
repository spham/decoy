<?php namespace Bkwld\Decoy\Models;

// Deps
use Bkwld\Upchuck\SupportsUploads;
use Config;
use DecoyURL;
use HTML;
use Illuminate\Auth\UserTrait;
use Illuminate\Auth\UserInterface;
use Illuminate\Auth\Reminders\RemindableTrait;
use Illuminate\Auth\Reminders\RemindableInterface;
use Hash;
use Input;
use Mail;
use Request;
use URL;

class Admin extends Base implements UserInterface, RemindableInterface {
	use UserTrait, RemindableTrait, SupportsUploads;

	/**
	 * The table associated with the model.  Explicitly declaring so that sub
	 * classes can use it
	 *
	 * @var string
	 */
	protected $table = 'admins';

	/**
	 * Validation rules
	 * 
	 * @var array
	 */
	public static $rules = [
		'first_name' => 'required',
		'last_name' => 'required',
		'image' => 'image',
		'email' => 'required|email|unique:admins,email',
		'password' => 'required',
		'confirm_password' => 'sometimes|required_with:password|same:password',
	];

	/**
	 * Uploadable attributes
	 * 
	 * @var array
	 */
	private $upload_attributes = ['image'];

	/**
	 * Orders instances of this model in the admin
	 * 
	 * @param  Illuminate\Database\Query\Builder $query
	 * @return void
	 */
	public function scopeOrdered($query) {
		$query->orderBy('last_name')->orderBy('first_name');
	}

	/**
	 * Tweak some validation rules
	 *
	 * @param Illuminate\Validation\Validator $validation
	 */
	public function onValidating($validation) {

		// Only apply mods when editing an existing record
		if (!$this->exists) return;
		$rules = self::$rules;

		// Make password optional
		$rules = array_except($rules, 'password');

		// Ignore the current record when validating email
		$rules['email'] .= ','.$this->id;
		
		// Update rules
		$validation->setRules($rules);
	}

	/**
	 * New admin callbacks
	 *
	 * @return void 
	 */
	public function onCreating() {
		if (Input::has('_send_email')) $this->sendCreateEmail();
		$this->active = 1;
	}

	/**
	 * Admin updating callbacks
	 *
	 * @return void 
	 */
	public function onUpdating() {
		if (Input::has('_send_email')) $this->sendUpdateEmail();
	}

	/**
	 * Callbacks regardless of new or old
	 *
	 * @return void 
	 */
	public function onSaving() {

		// If the password is changing, hash it
		if ($this->isDirty('password')) {
			$this->password = Hash::make($this->password);
		}
	}

	/**
	 * Send creation email
	 *
	 * @return void 
	 */
	public function sendCreateEmail() {

		// Prepare data for mail
		$admin = app('decoy.auth')->user();
		$email = array(
			'first_name' => $admin->first_name,
			'last_name' => $admin->last_name,
			'email' => Input::get('email'),
			'url' => Request::root().'/'.Config::get('decoy::core.dir'),
			'root' => Request::root(),
			'password' => Input::get('password'),
		);
	
		// Send the email
		Mail::send('decoy::emails.create', $email, function($m) use ($email) {
			$m->to($email['email'], $email['first_name'].' '.$email['last_name']);
			$m->subject('Welcome to the '.Config::get('decoy::site.name').' admin site');
			$m->from(Config::get('decoy::core.mail_from_address'), Config::get('decoy::core.mail_from_name'));
		});
	}

	/**
	 * Send update email
	 *
	 * @return void 
	 */
	public function sendUpdateEmail() {
		
		// Prepare data for mail
		$admin = app('decoy.auth')->user();
		$email = array(
			'editor_first_name' => $admin->first_name,
			'editor_last_name' => $admin->last_name,
			'first_name' =>Input::get('first_name'),
			'last_name' =>Input::get('last_name'),
			'email' => Input::get('email'),
			'password' =>Input::get('password'),
			'url' => Request::root().'/'.Config::get('decoy::core.dir'),
			'root' => Request::root(),
		);
		
		// Send the email
		Mail::send('decoy::emails.update', $email, function($m) use ($email) {
			$m->to($email['email'], $email['first_name'].' '.$email['last_name']);
			$m->subject('Your '.Config::get('decoy::site.name').' admin account info has been updated');
			$m->from(Config::get('decoy::core.mail_from_address'), Config::get('decoy::core.mail_from_name'));
		});
	}

	/**
	 * A shorthand for getting the admin name as a string
	 *
	 * @return string 
	 */
	public function getNameAttribute() {
		return $this->getAdminTitleAttribute();
	}

	/**
	 * Produce the title for the list view
	 * 
	 * @return string
	 */
	public function getAdminTitleHtmlAttribute() {
		if (isset($this->image)) return parent::getAdminTitleHtmlAttribute();
		return "<img src='".HTML::gravatar($this->email)."' class='gravatar'/> "
			.$this->getAdminTitleAttribute();
	}

	/**
	 * Show a badge if the user is the currently logged in
	 * 
	 * @return string
	 */
	public function getAdminStatusAttribute() {
		$html ='';
		
		// If row is you
		if ($this->id == app('decoy.auth')->user()->id) {
			$html .= '<span class="label label-info">You</span>';
		}

		// If row is disabled
		if ($this->disabled()) {
			$html .= '<a href="'.URL::to(DecoyURL::relative('enable', $this->id)).'" class="label label-warning js-tooltip" title="Click to enable login">Disabled</a>';
		}

		// Return HTML
		return $html;
	}

	/**
	 * Check if admin is banned
	 * 
	 * @return boolean true if banned
	 */
	public function disabled() {
		return !$this->active;
	}

}