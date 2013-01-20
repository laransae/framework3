<?php namespace Laravel\Mail;

use Closure;
use Swift_Mailer;
use Swift_Message;
use Laravel\Log;
use Laravel\View;
use Laravel\IoC;

class Mailer {

	/**
	 * The Swift Mailer instance.
	 *
	 * @var Swift_Mailer
	 */
	protected $swift;

	/**
	 * The global from address and name.
	 *
	 * @var array
	 */
	protected $from;

	/**
	 * Indicates if the actual sending is disabled.
	 *
	 * @var bool
	 */
	protected $pretending = false;

	/**
	 * Create a new Mailer instance.
	 *
	 * @param Illuminate\View\Environment $views
	 * @param Swift_Mailer $swift
	 * @return void
	 */
	public function __construct( Swift_Mailer $mailer = null ) {
		$this->swift = ( $mailer == null ) ? IoC::resolve( 'swift.mailer' ) : $mailer;
	}

	/**
	 * Set the global from address and name.
	 *
	 * @param string  $address
	 * @param string  $name
	 * @return void
	 */
	public function alwaysFrom( $address, $name = null ) {
		$this->from = compact( 'address', 'name' );
	}

	/**
	 * Send a new message using a view.
	 *
	 * @param string|array $view
	 * @param array   $data
	 * @param Closure|string $callback
	 * @return void
	 */
	public static function send( $view, array $data, $callback ) {
		//$mailer = new static;
		$mailer = IoC::resolve( 'mailer' );
		return $mailer->sendMessage( $view, $data, $callback );
	}

	/**
	 * Send a new message using a view.
	 *
	 * @param string|array $view
	 * @param array   $data
	 * @param Closure|string $callback
	 * @return void
	 */
	public function sendMessage( $view, array $data, $callback ) {
		if ( is_array( $view ) ) list( $view, $plain ) = $view;

		$data['message'] = $message = $this->createMessage();

		$this->callMessageBuilder( $callback, $message );

		// Once we have retrieved the view content for the e-mail we will set the body
		// of this message using the HTML type, which will provide a simple wrapper
		// to creating view based emails that are able to receive arrays of data.
		$content = $this->getView( $view, $data );

		$message->setBody( $content, 'text/html' );

		if ( isset( $plain ) ) {
			$message->addPart( $this->getView( $plain, $data ), 'text/plain' );
		}

		return $this->sendSwiftMessage( $message->getSwiftMessage() );
	}

	/**
	 * Send a Swift Message instance.
	 *
	 * @param Swift_Message $message
	 * @return void
	 */
	protected function sendSwiftMessage( $message ) {
		if ( ! $this->pretending ) {
			return $this->swift->send( $message );
		}
		elseif ( isset( $this->logger ) ) {
			$this->logMessage( $message );
		}
	}

	/**
	 * Log that a message was sent.
	 *
	 * @param Swift_Message $message
	 * @return void
	 */
	protected function logMessage( $message ) {
		$emails = implode( ', ', array_keys( $message->getTo() ) );

		Log::info( "Pretending to mail message to: {$emails}" );
	}

	/**
	 * Call the provided message builder.
	 *
	 * @param Closure|string $callback
	 * @param Laravel\Mail\Message $message
	 * @return void
	 */
	protected function callMessageBuilder( $callback, $message ) {
		if ( $callback instanceof Closure ) {
			return call_user_func( $callback, $message );
		}

		throw new \InvalidArgumentException( "Callback is not valid." );
	}

	/**
	 * Create a new message instance.
	 *
	 * @return Laravel\Mail\Message
	 */
	protected function createMessage() {
		$message = new Message( new Swift_Message );

		// If a global from address has been specified we will set it on every message
		// instances so the developer does not have to repeat themselves every time
		// they create a new message. We will just go ahead and push the address.
		if ( isset( $this->from['address'] ) ) {
			$message->from( $this->from['address'], $this->from['name'] );
		}

		return $message;
	}

	/**
	 * Render the given view.
	 *
	 * @param string  $view
	 * @param array   $data
	 * @return Laravel\View
	 */
	protected function getView( $view, $data ) {
		return View::make( $view, $data )->render();
	}

	/**
	 * Tell the mailer to not really send messages.
	 *
	 * @param bool    $value
	 * @return void
	 */
	public function pretend( $value = true ) {
		$this->pretending = $value;
	}

	/**
	 * Get the Swift Mailer instance.
	 *
	 * @return Swift_Mailer
	 */
	public function getSwiftMailer() {
		return $this->swift;
	}

	/**
	 * Set the Swift Mailer instance.
	 *
	 * @param Swift_Mailer $swift
	 * @return void
	 */
	public function setSwiftMailer( $swift ) {
		$this->swift = $swift;
	}

}
