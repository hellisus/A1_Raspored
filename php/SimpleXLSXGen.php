<?php

class SimpleXLSXGen {

    public $curSheet;
    protected $sheets;
    protected $template;
    protected $defaultFont;
    protected $defaultFontSize;

    public function __construct() {
        $this->curSheet = -1;
        $this->defaultFont = 'Calibri';
        $this->defaultFontSize = 11;
        $this->sheets = [ ['name' => 'Sheet1', 'rows' => [], 'hyperlinks' => []] ];
        $this->addSheet( 'Sheet1' );
    }

    public static function fromArray( array $rows, $sheetName = null ) {
        $xlsx = new self();
        if ( $sheetName ) {
             $xlsx->sheets[0]['name'] = $sheetName;
        }
        $xlsx->addRows( $rows );
        return $xlsx;
    }

    public function addSheet( $name ) {
        $this->curSheet++;
        $this->sheets[ $this->curSheet ] = [ 'name' => $name, 'rows' => [], 'hyperlinks' => [] ];
        return $this;
    }

    public function addRows( $rows ) {
        foreach ( $rows as $row ) {
            $this->addRow( $row );
        }
        return $this;
    }

    public function addRow( $row ) {
        $this->sheets[ $this->curSheet ]['rows'][] = $row;
        return $this;
    }

    public function downloadAs( $filename ) {
        
        if (ob_get_length()) ob_end_clean();
        
        $tmp = tempnam( sys_get_temp_dir(), 'xlsx' );
        $this->saveAs( $tmp );
        
        if (!file_exists($tmp)) {
            throw new Exception("Failed to create temporary file");
        }

        header( 'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Content-Length: ' . filesize( $tmp ) );
        header( 'Content-Transfer-Encoding: binary' );
        header( 'Cache-Control: must-revalidate' );
        header( 'Pragma: public' );

        readfile( $tmp );
        unlink( $tmp );
    }

    public function saveAs( $filename ) {
        $fh = fopen( $filename, 'w' );
        if ( ! $fh ) {
            return false;
        }
        fclose( $fh );

        $zip = new ZipArchive();
        if ( ! $zip->open( $filename, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
            return false;
        }

        $zip->addFromString( '[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/></Types>' );

        $zip->addFromString( '_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>' );

        $zip->addFromString( 'xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>' );

        $zip->addFromString( 'xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Sheet1" sheetId="1" r:id="rId1"/></sheets></workbook>' );

        $zip->addFromString( 'xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="1"><font><sz val="'.$this->defaultFontSize.'"/><name val="'.$this->defaultFont.'"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs></styleSheet>' );

        $zip->addFromString( 'docProps/core.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dcterms:created xsi:type="dcterms:W3CDTF">' . date( 'Y-m-d\TH:i:s\Z' ) . '</dcterms:created><dc:creator>SimpleXLSXGen</dc:creator></cp:coreProperties>' );

        $zip->addFromString( 'docProps/app.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"><Application>SimpleXLSXGen</Application></Properties>' );

        $rows_xml = '';
        $idx = 0;
        foreach ( $this->sheets[0]['rows'] as $k => $row ) {
            $rows_xml .= '<row r="' . ( $idx + 1 ) . '">';
            foreach ( $row as $i => $value ) {
                $val = self::esc( $value );
                $rows_xml .= '<c r="' . $this->num2alpha( $i ) . ( $idx + 1 ) . '" t="inlineStr"><is><t>' . $val . '</t></is></c>';
            }
            $rows_xml .= '</row>';
            $idx++;
        }

        $zip->addFromString( 'xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $rows_xml . '</sheetData></worksheet>' );

        $zip->close();
    }

    public static function esc( $str ) {
        // Remove invalid XML characters
        $str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $str);
        return htmlspecialchars( (string)$str, ENT_QUOTES );
    }

    protected function num2alpha( $n ) {
        for ( $r = ""; $n >= 0; $n = intval( $n / 26 ) - 1 ) {
            $r = chr( $n % 26 + 0x41 ) . $r;
        }
        return $r;
    }
}
