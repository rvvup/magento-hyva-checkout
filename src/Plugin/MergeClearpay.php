<?php
declare(strict_types=1);

namespace Rvvup\PaymentsHyvaCheckout\Plugin;

use Hyva\Theme\Service\CurrentTheme;
use Magento\Framework\View\Model\Layout\Merge;

class MergeClearpay
{

    /** @var CurrentTheme */
    private $theme;

    /**
     * @param CurrentTheme $theme
     */
    public function __construct(
        CurrentTheme $theme
    ) {
        $this->theme = $theme;
    }

    /**
     * @param Merge $subject
     * @param array|string $handles
     * @return array
     */
    public function beforeLoad(Merge $subject, $handles  =  []): array
    {
        if (is_string($handles)) {
            $handles = [$handles];
        }
        if ($this->theme->isHyva()) {
            /** Add clearpay to the page */
            $handles[] = 'hyva_rvvup_clearpay';
        }

        return [$handles];
    }
}
