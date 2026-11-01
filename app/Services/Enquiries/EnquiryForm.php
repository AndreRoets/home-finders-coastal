<?php

namespace App\Services\Enquiries;

/**
 * The public forms that produce an enquiry. The backing value is stored on the
 * enquiry and sent to GA4 as the `form_name` of the `form_submit` event.
 */
enum EnquiryForm: string
{
    /** The contact page form. */
    case Contact = 'contact';

    /** The enquiry form in the property detail sidebar. */
    case PropertyEnquiry = 'property_enquiry';

    public function label(): string
    {
        return match ($this) {
            self::Contact => 'Contact page',
            self::PropertyEnquiry => 'Property enquiry',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $form): array => ['value' => $form->value, 'label' => $form->label()],
            self::cases(),
        );
    }
}
