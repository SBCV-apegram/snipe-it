<?php
/*****************************************************************************
 * Template for Dymo LabelWriter 30374 stock
 * 
 * Suggested use:
 * ┌──────────────────────────────────────────────────────────┐
 * |             FIELD 0 VALUE  (Asset Name)                  |
 * |             full width, bold, centered                   |
 * ├──────────────────────┬───────────────────────────────────┤
 * |                      | Field 1:                          |
 * |                      | Value                             |
 * |     2D BARCODE       | Field 2:                          |
 * |                      | Value                             |
 * |      ASSET TAG       | Field 4:                          |
 * |                      | Value                             |
 * ├──────────────────────┴───────────────────────────────────┤
 * |                     1D BARCODE                           |
 * ├──────────────────────────────────────────────────────────┤
 * |                  TITLE, CENTERED                         |
 * └──────────────────────────────────────────────────────────┘
 * 
 */
namespace App\Models\Labels\Tapes\Dymo;

use App\Helpers\Helper;

class LabelWriter_30374 extends LabelWriter
{
    private const BARCODE1D_HEIGHT = 5.00;
    private const BARCODE_MARGIN = 2.00;

    private const TAG_SIZE = 3.50;

    private const TITLE_SIZE = 4.50;
    private const TITLE_MARGIN = 0.75;

    private const TOP_FIELD_SIZE = 5.00;
    private const TOP_FIELD_MARGIN = 1.50;

	private const FIELD_SIZE = 2.50;
	private const FIELD_MARGIN = 0.30;
	
	private const LABEL_SIZE = 2.50;
	private const LABEL_MARGIN = 0.40;

    public function getUnit()
    {
        return 'mm';
    }

    public function getWidth()
    {
        return 89;
    }

    public function getHeight()
    {
        return 51;
    }

    public function getSupportAssetTag()
    {
        return true;
    }

    public function getSupport1DBarcode()
    {
        return true;
    }

    public function getSupport2DBarcode()
    {
        return true;
    }

    public function getSupportFields()
    {
        return 5;
    }

    public function getSupportLogo()
    {
        return false;
    }

    public function getSupportTitle()
    {
        return true;
    }

    public function getBarcodeSize(): float
    {
        return self::BARCODE1D_HEIGHT;
    }

    public function getBarcodeMargin(): float
    {
        return self::BARCODE_MARGIN;
    }

    public function getTagSize(): float
    {
        return self::TAG_SIZE;
    }

    public function getTitleSize(): float
    {
        return self::TITLE_SIZE;
    }

    public function getTitleMargin(): float
    {
        return self::TITLE_MARGIN;
    }

    public function getLabelSize(): float
    {
        return self::LABEL_SIZE;
    }

    public function getLabelMargin(): float
    {
        return self::LABEL_MARGIN;
    }

    public function getFieldSize(): float
    {
        return self::FIELD_SIZE;
    }

    public function getFieldMargin(): float
    {
        return self::FIELD_MARGIN;
    }

    protected function getContentEditorConfig(): array
    {
        return [
            'barcode_size' => $this->getBarcodeSize(),
            'barcode_margin' => $this->getBarcodeMargin(),
            'barcode1D_v_align' => 'B',

            'barcode_2d_size' => 27.0,
            'barcode2D_h_align' => 'L',
            'barcode2D_v_align' => 'T',

            'tag_font_size' => $this->getTagSize(),
            'tag_alignment' => 'C',
            'tag_position_mode' => 'under_barcode',
            'tag_font' => 'freesans',

            'title_font_size' => $this->getTitleSize(),
            'title_margin' => $this->getTitleMargin(),
            'title_alignment' => 'C',
            'title_font' => 'freesans',

            'field_label_font_size' => $this->getLabelSize(),
            'field_label_margin' => $this->getLabelMargin(),
            'field_label_font' => 'freesans',

            'field_value_font_size' => $this->getFieldSize(),
            'field_value_margin' => $this->getFieldMargin(),
            'field_value_font' => 'freemono',

            'text_render_mode' => 'block',
        ];
    }

    public function write($pdf, $record)
    {
        $pa = $this->getPrintableArea();

        $fullX = $pa->x1;
        $fullWidth = $pa->w;

        $currentX = $pa->x1;
        $currentY = $pa->y1;

        $fields = collect($record->get('fields') ?? [])
            ->take($this->getSupportFields())
            ->values();

        $title = $record->has('title')
            ? trim((string) $record->get('title'))
            : '';

        /*
         * Reserve the bottom portion of the label.
         *
         * The title is placed below the 1D barcode and centered across
         * the entire printable width.
         */
        $titleHeight = $title !== ''
            ? self::TITLE_SIZE + self::TITLE_MARGIN
            : 0.0;

        $barcode1DHeight = $record->has('barcode1d')
            ? self::BARCODE1D_HEIGHT
            : 0.0;

        $bottomSectionHeight = 0.0;

        if ($barcode1DHeight > 0) {
            $bottomSectionHeight += $barcode1DHeight;
        }

        if ($barcode1DHeight > 0 && $titleHeight > 0) {
            $bottomSectionHeight += self::TITLE_MARGIN;
        }

        if ($titleHeight > 0) {
            $bottomSectionHeight += $titleHeight;
        }

        /*
         * Field 0
         *
         * Field 0 is removed from the normal field collection and rendered
         * across the full printable width at the top. It uses a large,
         * bold, title-style presentation.
         */
        $topField = $fields->shift();

        if ($topField !== null) {
            $topFieldValue = trim((string) ($topField['value'] ?? ''));

            /*
             * If the field has no value but does have a label, use the
             * label as the displayed top text.
             */
            if ($topFieldValue === '') {
                $topFieldValue = trim(
                    rtrim((string) ($topField['label'] ?? ''), ':')
                );
            }

            if ($topFieldValue !== '') {
                $topFieldHeight =
                    self::TOP_FIELD_SIZE +
                    self::TOP_FIELD_MARGIN;

                static::writeText(
                    $pdf,
                    $topFieldValue,
                    $fullX,
                    $currentY,
                    'freesans',
                    'b',
                    self::TOP_FIELD_SIZE,
                    'C',
                    $fullWidth,
                    self::TOP_FIELD_SIZE,
                    true,
                    0
                );

                $currentY += $topFieldHeight;
            }
        }

        /*
         * Determine the vertical space available for the middle section.
         *
         * This area contains the 2D barcode, asset tag, and fields 1-4.
         */
        $middleBottom = $pa->y2 - $bottomSectionHeight;

        if ($bottomSectionHeight > 0) {
            $middleBottom -= self::BARCODE_MARGIN;
        }

        $middleHeight = max(
            0,
            $middleBottom - $currentY
        );

        $middleX = $fullX;
        $middleWidth = $fullWidth;

        /*
         * 2D barcode
         *
         * The 2D barcode remains in the left column. The asset tag is
         * centered directly beneath it.
         */
        if ($record->has('barcode2d') && $middleHeight > 0) {
            $maximumBarcodeHeight = max(
                0,
                $middleHeight - self::TAG_SIZE
            );

            $barcodeSize = min(
                $maximumBarcodeHeight,
                $middleWidth * 0.38
            );

            if ($barcodeSize > 0) {
                static::write2DBarcode(
                    $pdf, $record->get('barcode2d')->content, $record->get('barcode2d')->type,
                    $middleX, $currentY,
                    $barcodeSize, $barcodeSize
                );

                if ($record->has('tag')) {
                    static::writeText(
                        $pdf, $record->get('tag'),
                        $middleX, $currentY + $barcodeSize,
                        'freesans', 'b', self::TAG_SIZE, 'C',
                        $barcodeSize, self::TAG_SIZE, true, 0
                    );
                }

                $middleX += $barcodeSize + self::BARCODE_MARGIN;
                $middleWidth -= $barcodeSize + self::BARCODE_MARGIN;
            }
        }

        /*
         * Remaining fields
         *
         * Fields 1-4 are placed in the middle section to the right of
         * the 2D barcode.
         */
        if ($fields->isNotEmpty() && $middleWidth > 0 && $middleHeight > 0) {
            $fieldLayout = Helper::labelFieldLayoutScaling(
                pdf: $pdf,
                fields: $fields,
                currentX: $middleX,
                usableWidth: $middleWidth,
                usableHeight: $middleHeight,
                baseLabelSize: self::LABEL_SIZE,
                baseFieldSize: self::FIELD_SIZE,
                baseFieldMargin: self::FIELD_MARGIN,
                title: null,
                baseTitleSize: self::TITLE_SIZE,
                baseTitleMargin: self::TITLE_MARGIN,
                baseLabelPadding: 2.0,
                baseGap: 2.0,
                maxScale: 2.0,
                labelFont: 'freesans',
            );

			$fieldY = $currentY;
			
			foreach ($fields as $field) {
			    $rawLabel = $field['label'] ?? null;
			    $value = (string) ($field['value'] ?? '');
			
			    /*
			     * No label: give the value the entire row and full width.
			     */
			    if (! is_string($rawLabel) || trim($rawLabel) === '') {
			        static::writeText(
			            $pdf, $value,
			            $middleX, $fieldY,
			            'freemono', '', $fieldLayout['fieldSize'], 'L',
			            $middleWidth, $fieldLayout['fieldSize'], true, 0, 0.01
			        );
			
			        $fieldY += $fieldLayout['fieldSize'] + self::FIELD_MARGIN;
			
			        continue;
			    }
			
			    /*
			     * Label on its own line.
			     * Smaller than the field value and allowed the full width.
			     */
			    $labelText = rtrim($rawLabel, ':').':';
			
			    static::writeText(
			        $pdf, $labelText,
			        $middleX, $fieldY,
			        'freesans', '', self::LABEL_SIZE, 'L',
			        $middleWidth, self::LABEL_SIZE, true, 0
			    );
			
			    $fieldY += self::LABEL_SIZE + self::LABEL_MARGIN;
			
			    /*
			     * Value on the next line.
			     * It receives the entire width of the right-hand column.
			     */
			    static::writeText(
			        $pdf, $value,
			        $middleX, $fieldY,
			        'freemono', '', $fieldLayout['fieldSize'],'L',
			        $middleWidth, $fieldLayout['fieldSize'], true, 0, 0.01
			    );
			
			    $fieldY += $fieldLayout['fieldSize'] + self::FIELD_MARGIN;
			}
		}

        /*
         * 1D barcode
         *
         * The barcode is placed across the full printable width near
         * the bottom of the label.
         */
        $bottomY = $pa->y2 - $bottomSectionHeight;

        if ($record->has('barcode1d')) {
            static::write1DBarcode(
                $pdf, $record->get('barcode1d')->content, $record->get('barcode1d')->type,
                $fullX, $bottomY,
                $fullWidth, self::BARCODE1D_HEIGHT
            );

            $bottomY += self::BARCODE1D_HEIGHT;

            if ($title !== '') {
                $bottomY += self::TITLE_MARGIN;
            }
        }

        /*
         * Title
         *
         * The title is rendered below the 1D barcode, centered across
         * the full printable width.
         */
        if ($title !== '') {
            static::writeText(
                $pdf, $title,
                $fullX, $bottomY,
                'freesans', 'b', self::TITLE_SIZE, 'C',
                $fullWidth, self::TITLE_SIZE, true, 0
            );
        }
    }
}