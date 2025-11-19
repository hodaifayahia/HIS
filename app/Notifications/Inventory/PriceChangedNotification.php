<?php

namespace App\Notifications\Inventory;

use App\Models\Inventory\ServiceGroupProductPricing;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriceChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public ServiceGroupProductPricing $oldPricing;

    public ServiceGroupProductPricing $newPricing;

    public User $changedBy;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        ServiceGroupProductPricing $oldPricing,
        ServiceGroupProductPricing $newPricing,
        User $changedBy
    ) {
        $this->oldPricing = $oldPricing;
        $this->newPricing = $newPricing;
        $this->changedBy = $changedBy;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $productName = $this->newPricing->is_pharmacy
            ? ($this->newPricing->pharmacyProduct->name ?? 'Unknown')
            : ($this->newPricing->product->product_name ?? 'Unknown');

        $serviceGroupName = $this->newPricing->serviceGroup->name ?? 'Unknown';

        $oldPrice = number_format($this->oldPricing->selling_price, 2);
        $newPrice = number_format($this->newPricing->selling_price, 2);
        $priceDiff = $this->newPricing->selling_price - $this->oldPricing->selling_price;
        $priceChange = $priceDiff > 0 ? 'increased' : 'decreased';
        $priceDiffFormatted = number_format(abs($priceDiff), 2);

        return (new MailMessage)
            ->subject("Price Change Alert: {$productName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("A price change has been made for **{$productName}** in service group **{$serviceGroupName}**.")
            ->line("**Old Price:** {$oldPrice} DZD")
            ->line("**New Price:** {$newPrice} DZD")
            ->line("**Change:** Price {$priceChange} by {$priceDiffFormatted} DZD")
            ->line("**Changed by:** {$this->changedBy->name}")
            ->line("**Notes:** {$this->newPricing->notes}")
            ->action('View Pricing History', url('/apps/inventory/pricing-manager'))
            ->line('Thank you for using our Hospital Information System!');
    }

    /**
     * Get the array representation of the notification (for database).
     */
    public function toArray($notifiable): array
    {
        $productName = $this->newPricing->is_pharmacy
            ? ($this->newPricing->pharmacyProduct->name ?? 'Unknown')
            : ($this->newPricing->product->product_name ?? 'Unknown');

        return [
            'type' => 'price_change',
            'product_name' => $productName,
            'service_group_id' => $this->newPricing->service_group_id,
            'service_group_name' => $this->newPricing->serviceGroup->name ?? 'Unknown',
            'old_price' => $this->oldPricing->selling_price,
            'new_price' => $this->newPricing->selling_price,
            'price_difference' => $this->newPricing->selling_price - $this->oldPricing->selling_price,
            'changed_by' => $this->changedBy->name,
            'changed_by_id' => $this->changedBy->id,
            'notes' => $this->newPricing->notes,
            'effective_from' => $this->newPricing->effective_from,
        ];
    }
}
