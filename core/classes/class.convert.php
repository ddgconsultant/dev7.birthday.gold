<?php

// Usage
// //$convert = new Convert();

// // Convert to PDF
// $pdfOutput = 'google.pdf';
// $pdfResult = $convert->toPDF('http://www.google.com', $pdfOutput);
// echo $pdfResult . "\n";

// // Convert to JPG
// $jpgOutput = 'google.jpg';
// $jpgResult = $convert->toJPG('http://www.google.com', $jpgOutput);
// echo $jpgResult . "\n";

// // Convert to PNG
// $pngOutput = 'google.png';
// $pngResult = $convert->toPNG('http://www.google.com', $pngOutput);
// echo $pngResult . "\n";

class Convert
{
    private $wkhtmltopdfPath;
    private $wkhtmltoimagePath;

    public function __construct()
    {
        $this->wkhtmltopdfPath = $_SERVER['DOCUMENT_ROOT'] . '/core/applications/wkhtmltox/bin/wkhtmltopdf.exe';
        $this->wkhtmltoimagePath = $_SERVER['DOCUMENT_ROOT'] . '/core/applications/wkhtmltox/bin/wkhtmltoimage.exe';
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function toPDF($url, $outputFile)
    {
        $command = "{$this->wkhtmltopdfPath} $url $outputFile";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return "PDF successfully generated.";
        } else {
            return "Failed to generate PDF. " . implode("\n", $output);
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function toJPG($url, $outputFile)
    {
        $command = "{$this->wkhtmltoimagePath} $url $outputFile";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return "JPG successfully generated.";
        } else {
            return "Failed to generate JPG. " . implode("\n", $output);
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function toPNG($url, $outputFile)
    {
        $command = "{$this->wkhtmltoimagePath} $url $outputFile";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return "PNG successfully generated.";
        } else {
            return "Failed to generate PNG. " . implode("\n", $output);
        }
    }



    # ##--------------------------------------------------------------------------------------------------------------------------------------------------
    public function toImage($url, $outputFile, $format = 'jpg')
    {
        $outputFileWithFormat = "{$outputFile}.{$format}";
        $command = "{$this->wkhtmltoimagePath} $url $outputFileWithFormat";
        exec($command, $output, $return_var);

        if ($return_var === 0) {
            return "{$format} successfully generated.";
        } else {
            return "Failed to generate {$format}. " . implode("\n", $output);
        }
    }
}
