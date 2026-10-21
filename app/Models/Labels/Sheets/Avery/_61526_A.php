<?php
/************************************************************
 * 2D Barcode label for Avery PermaTrack 61526 label sheets
 * 
 *  Suggested use:
 * ┌────────────────────────────────────────┐
 * │           {COMPANY NAME}               │
 * │ ┌───────────┐ {label}                  │
 * │ │           │ {PHONE} (larger text)    │
 * │ │    QR     │ {label}                  │
 * │ │   CODE    │ {SERIAL NUM}             │
 * │ │           │ {ASSET NAME}             │
 * │ └───────────┘                          │
 * └────────────────────────────────────────┘ 
 * 
 ************************************************************/

namespace App\Models\Labels\Sheets\Avery;

class _61526_A extends _61526
{
    private const BARCODE_MARGIN = 0.04;

    private const TITLE_SIZE = 0.10;
    private const TITLE_MARGIN = 0.015;

    private const LABEL_SIZE = 0.050;
    private const LABEL_MARGIN = -0.003;

    private const FIELD_SIZE = 0.080;
    private const FIELD_MARGIN = 0.004;

    private const SUPPORT_LABEL_SIZE = 0.055;
    private const SUPPORT_SIZE = 0.105;
    private const SUPPORT_MARGIN = 0.008;

    protected string $titleFont = 'freesans';
    protected string $fieldLabelFont = 'freesans';
    protected string $fieldValueFont = 'freemono';

    public function getBarcodeMargin()
    {
        return self::BARCODE_MARGIN;
    }

    public function getTitleSize()
    {
        return self::TITLE_SIZE;
    }

    public function getTitleMargin()
    {
        return self::TITLE_MARGIN;
    }

    public function getLabelSize()
    {
        return self::LABEL_SIZE;
    }

    public function getLabelMargin()
    {
        return self::LABEL_MARGIN;
    }

    public function getFieldSize()
    {
        return self::FIELD_SIZE;
    }

    public function getFieldMargin()
    {
        return self::FIELD_MARGIN;
    }

    public function getUnit()
    {
        return 'in';
    }

    public function getLabelMarginTop()
    {
        return 0.04;
    }

    public function getLabelMarginBottom()
    {
        return 0.04;
    }

    public function getLabelMarginLeft()
    {
        return 0.04;
    }

    public function getLabelMarginRight()
    {
        return 0.04;
    }

    public function getSupportAssetTag()
    {
        return false;
    }

    public function getSupport1DBarcode()
    {
        return false;
    }

    public function getSupport2DBarcode()
    {
        return true;
    }

    public function getSupportFields()
    {
        return 3;
    }

    public function getSupportLogo()
    {
        return false;
    }

    public function getSupportTitle()
    {
        return true;
    }

    public function getTitleFont(): string
    {
        return $this->titleFont;
    }

    public function getFieldLabelFont(): string
    {
        return $this->fieldLabelFont;
    }

    public function getFieldValueFont(): string
    {
        return $this->fieldValueFont;
    }

    protected function getSupportsEditorConfig(): array
    {
        return [
            'asset_tag' => $this->getSupportAssetTag(),
            'barcode_1d' => $this->getSupport1DBarcode(),
            'barcode_2d' => $this->getSupport2DBarcode(),
            'fields' => $this->getSupportFields(),
            'logo' => $this->getSupportLogo(),
            'title' => $this->getSupportTitle(),
        ];
    }

    protected function getContentEditorConfig(): array
    {
        return [
            'barcode_margin' => $this->getBarcodeMargin(),

            'barcode_2d_size' =>
                $this->getLabelPrintableArea()->h
                - self::TITLE_SIZE
                - self::TITLE_MARGIN,

            'title_font_size' => $this->getTitleSize(),
            'title_margin' => $this->getTitleMargin(),

            'field_label_font_size' => $this->getLabelSize(),
            'field_label_margin' => $this->getLabelMargin(),

            'field_value_font_size' => $this->getFieldSize(),
            'field_value_margin' => $this->getFieldMargin(),

            'title_font' => $this->getTitleFont(),
            'field_label_font' => $this->getFieldLabelFont(),
            'field_value_font' => $this->getFieldValueFont(),

            'title_position' => 'top',
            'title_offset_x' => 0.595,
        ];
    }

    public function write($pdf, $record)
    {
        $pa = $this->getLabelPrintableArea();

        $currentX = $pa->x1;
        $currentY = $pa->y1;
        $usableWidth = $pa->w;
        $usableHeight = $pa->h;

        // Company name
        if ($record->has('title')) {
            static::writeText(
                $pdf,
                $record->get('title'),
                $pa->x1,
                $pa->y1,
                'freesans',
                'B',
                self::TITLE_SIZE,
                'C',
                $pa->w,
                self::TITLE_SIZE,
                true,
                0
            );

            $currentY += self::TITLE_SIZE + self::TITLE_MARGIN;
            $usableHeight -= self::TITLE_SIZE + self::TITLE_MARGIN;
        }

        // QR / 2D barcode
        $barcodeSize = $usableHeight;

        if ($record->has('barcode2d')) {
            static::write2DBarcode(
                $pdf,
                $record->get('barcode2d')->content,
                $record->get('barcode2d')->type,
                $currentX,
                $currentY,
                $barcodeSize,
                $barcodeSize
            );

            $currentX += $barcodeSize + self::BARCODE_MARGIN;
            $usableWidth -= $barcodeSize + self::BARCODE_MARGIN;
        }

        /*
         * Expected field order:
         *   0: Support number
         *   1: Serial number
         *   2: Asset name (optional)
         */
        foreach ($record->get('fields') as $index => $field) {
            $value = trim((string) ($field['value'] ?? ''));

            // Do not reserve space for an empty optional field.
            if ($value === '') {
                continue;
            }

            if ($index === 0) {
                /*
                 * Support number gets special treatment:
                 * smaller SUPPORT heading with larger bold number.
                 */
                static::writeText(
                    $pdf,
                    strtoupper((string) ($field['label'] ?? '')),
                    $currentX,
                    $currentY,
                    'freesans',
                    '',
                    self::SUPPORT_LABEL_SIZE,
                    'L',
                    $usableWidth,
                    self::SUPPORT_LABEL_SIZE,
                    true,
                    0
                );

                $currentY += self::SUPPORT_LABEL_SIZE;

                static::writeText(
                    $pdf,
                    $value,
                    $currentX,
                    $currentY,
                    'freesans',
                    'B',
                    self::SUPPORT_SIZE,
                    'L',
                    $usableWidth,
                    self::SUPPORT_SIZE,
                    true,
                    0
                );

                $currentY +=
                    self::SUPPORT_SIZE +
                    self::SUPPORT_MARGIN;

                continue;
            }

            /*
             * Serial number and optional asset name use
             * the standard label/value treatment.
             */
            static::writeText(
                $pdf,
                strtoupper((string) ($field['label'] ?? '')),
                $currentX,
                $currentY,
                'freesans',
                '',
                self::LABEL_SIZE,
                'L',
                $usableWidth,
                self::LABEL_SIZE,
                true,
                0
            );

            $currentY +=
                self::LABEL_SIZE +
                self::LABEL_MARGIN;

            static::writeText(
                $pdf,
                $value,
                $currentX,
                $currentY,
                'freemono',
                'B',
                self::FIELD_SIZE,
                'L',
                $usableWidth,
                self::FIELD_SIZE,
                true,
                0,
                0.01
            );

            $currentY +=
                self::FIELD_SIZE +
                self::FIELD_MARGIN;
        }
    }
}