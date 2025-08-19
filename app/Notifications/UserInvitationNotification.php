<?php

namespace App\Notifications;

use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected UserInvitation $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(UserInvitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $acceptUrl = url("/invitations/accept/{$this->invitation->token}");

        $mail = (new MailMessage)
            ->subject('Invitation to Join '.$this->invitation->account->name.' on Service Vault')
            ->greeting('Hello'.($this->invitation->invited_name ? ' '.$this->invitation->invited_name : '').'!')
            ->line('You have been invited by '.$this->invitation->invitedBy->name.' to join '.$this->invitation->account->name.' on Service Vault.')
            ->line('Your role will be: '.$this->invitation->roleTemplate->name);

        // Add custom message if provided
        if ($this->invitation->message) {
            $mail->line('Message from '.$this->invitation->invitedBy->name.':')
                ->line('"'.$this->invitation->message.'"');
        }

        $mail->action('Accept Invitation', $acceptUrl)
            ->line('This invitation will expire on '.$this->invitation->expires_at->format('M j, Y \a\t g:i A'))
            ->line('If you did not expect this invitation, you can safely ignore this email.');

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'account_name' => $this->invitation->account->name,
            'role_name' => $this->invitation->roleTemplate->name,
            'invited_by' => $this->invitation->invitedBy->name,
            'expires_at' => $this->invitation->expires_at,
        ];
    }
}
