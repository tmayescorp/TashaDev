<?php

namespace WilokeListingTools\Middleware;

use WilokeListingTools\Framework\Helpers\GetSettings;
use WilokeListingTools\Framework\Routing\InterfaceMiddleware;

class IsLockedAddListing implements InterfaceMiddleware
{
    public $msg;

    public function handle(array $aOptions)
    {
        $userID = get_current_user_id();
        $reason = GetSettings::getUserMeta($userID, 'locked_addlisting');
        if (empty($reason)) {
            return true;
        }

        if ($reason === 'payment_dispute') {
            $aLockedReason = GetSettings::getUserMeta($userID, 'locked_addlisting_reason');

            $email     = \WilokeThemeOptions::getOptionDetail('email_from');
            $this->msg = sprintf(
                esc_html__('We regret to inform you that your account has been locked because there was a dispute in the following Payment ID: %d. Please email to %s to resolve this issue!',
                    'wiloke-listing-tools'),
                $aLockedReason['paymentID'], $email
            );
        } else {
            $this->msg = esc_html__('Your account has been locked', 'wiloke-listing-tools');
        }

        return false;
    }
}
