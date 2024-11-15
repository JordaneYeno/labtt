<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendDiffusion extends Mailable
{
    use Queueable, SerializesModels;
    
    public $details;
    public $subject;
    public $view;
    public $file;
    
    
    /**
     * Create a new message instance.
     * 
     * 
     *
     * @return void
     */
    public function __construct($details , $subject , $view , $file=null)
    {
        $this->details = $details;
        $this->subject = $subject;
        $this->view = $view;
        $this->file = $file;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('contact@bakoai.pro')
        ->subject($this->subject)
        ->view($this->view)
        ->attach($this->file);
        
    }

    
}
