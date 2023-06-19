<?php

namespace App\Http\Controllers;

use Laravel\Dusk\Browser;
use Illuminate\Http\Request;
use Laravel\Dusk\Chrome\ChromeProcess;
use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverKeys;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\WebDriverWait;
use Illuminate\Support\Facades\File;
//use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;


use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

use App\Models\FuncionarioModel;


class RpaController extends Controller{

    public function capturarDados(){//OK
        // Configuração do ChromeDriver
        $options = (new ChromeOptions())->addArguments([
            '--headless', // Executar em modo headless (sem interface gráfica)
            '--disable-gpu', // Desabilitar aceleração de GPU
            '--no-sandbox', // Executar sem sandbox
        ]);

        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);

        try {            
            $driver->get('https://testpages.herokuapp.com/styled/tag/table.html');

            $tableElement = $driver->findElement(WebDriverBy::id('mytable'));

            $rows = $tableElement->findElements(WebDriverBy::tagName('tr'));

            $tableData = [];
          
            foreach ($rows as $row) {
                $rowData = [];
                $cells = $row->findElements(WebDriverBy::tagName('td'));

                foreach ($cells as $cell) {
                    $cellData = $cell->getText();
                    $rowData[] = $cellData;
                }

                $dados[] = $rowData;
            }

            array_shift($dados);    

            foreach ($dados as $row) {
               // echo $row[0];
                $model = new FuncionarioModel();
                $model->nome = $row[0];
                $model->salario = $row[1];
                $model->save();
            }

            $driver->quit();

            return response()->json(['message' => 'Dados capturados com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }

    public function preencherFormulario(){        
        $options = (new ChromeOptions())->addArguments([
            //'--headless', // Executar em modo headless (sem interface gráfica)
            '--disable-gpu', // Desabilitar aceleração de GPU
            '--no-sandbox', // Executar sem sandbox
        ]);
        
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);
        
        $driver->get('https://testpages.herokuapp.com/styled/basic-html-form-test.html'); 

        try{      
            $driver->findElement(WebDriverBy::name('username'))->sendKeys('Willian');
            $driver->findElement(WebDriverBy::name('password'))->sendKeys('123456');
            $driver->findElement(WebDriverBy::name('comments'))->sendKeys('Teste da BeCare');
            

            //UPLOAD
            $inputFile = $driver->findElement(WebDriverBy::name('filename')); 
            $caminhoArquivo = 'C:\teste.txt';
            $inputFile->sendKeys($caminhoArquivo);

            //CHECKBOX           
            $valueCk = 'cb2';
            $checkbox = $driver->findElement(WebDriverBy::cssSelector('input[type="checkbox"][value="'.$valueCk.'"]'));
            
            if(!$checkbox->isSelected()){
                $checkbox->click();
            }    

            //RADIO
            $radiobutton = $driver->findElement(WebDriverBy::name('radioval'));
            // Verificar se o radiobutton não está selecionado
            if (!$radiobutton->isSelected()){
                // Selecionar o primeira radiobutton
                $radiobutton->click();
            }

            //MULTISELECT
            $select = $driver->findElement(WebDriverBy::name('multipleselect[]'));
            // Selecionar várias opções
            $options = $select->findElements(WebDriverBy::tagName('option'));
            foreach ($options as $option) {
                // Verificar o valor da opção
                if (($option->getAttribute('value') == 'ms1')||($option->getAttribute('value')=='ms2')) {
                    // Selecionar a primeira opção
                    $option->click();
                }
            }
            
            //DROPDOWN
            $dropdown = $driver->findElement(WebDriverBy::name('dropdown'));

            //Obter todos os elementos de opção
            $options = $dropdown->findElements(WebDriverBy::tagName('option'));
            foreach ($options as $option) {
                if ($option->getAttribute('value') == 'dd2') {
                    $option->click();              
                }
            }
            
            //usleep(5000000);
            // Submeter o formulário
            $driver->findElement(WebDriverBy::tagName('form'))->submit();

            $driver->wait()->until(
                WebDriverExpectedCondition::urlIs('https://testpages.herokuapp.com/styled/the_form_processor.php')
            );        
            
            return response()->json(['message' =>'Sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Falhou!']);
        }
    }


    public function baixarArquivo(){
        $options = (new ChromeOptions())->addArguments([
            '--headless', // Executar em modo headless (sem interface gráfica)
            '--disable-gpu', // Desabilitar aceleração de GPU
            '--no-sandbox', // Executar sem sandbox
        ]);
        
        $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
        $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities);       

        // Abre o link para download
        $driver->get('https://testpages.herokuapp.com/styled/download/download.html');

        // Localiza e clica no botão "Direct Link Download"
        $downloadButton = $driver->findElement(WebDriverBy::linkText('Direct Link Download'));
        $downloadButton->click();

        // Aguarda um tempo para o download ser concluído
        sleep(3);

        // Diretório de downloads
        $chromeDownloadsDir = 'C:\Users\Usuario\Downloads'; // Substitua pelo diretório correto

        // Localiza o arquivo baixado mais recente no diretório de downloads
        $latestFile = $this->getLatestDownloadedFile($chromeDownloadsDir);

        // Verifica se o arquivo foi encontrado
        if ($latestFile !== null) {
            $newFileName = 'TesteTKS';
            $newFilePath = $chromeDownloadsDir . '/' . $newFileName;
            File::move($latestFile, $newFilePath);

            return response()->json(['message' => 'O arquivo foi baixado e renomeado com sucesso.', 'new_file_name' => $newFileName]);
        } else {           
            return response()->json(['message' => 'Erro ao baixar o arquivo.']);
        }        
    }

    private function getLatestDownloadedFile($directory){
        $latestFile = null;
        $latestModifiedTime = 0;

        // Percorre todos os arquivos do diretório
        foreach (File::files($directory) as $file) {
            // Verifica se o arquivo foi modificado mais recentemente
            $modifiedTime = $file->getMTime();
            if ($modifiedTime > $latestModifiedTime) {
                $latestFile = $file;
                $latestModifiedTime = $modifiedTime;
            }
        }

        return $latestFile;
    } 

    public function realizarUpload(){
        try{
            $options = (new ChromeOptions())->addArguments([
                //'--headless', // Executar em modo headless (sem interface gráfica)
                '--disable-gpu', // Desabilitar aceleração de GPU
                '--no-sandbox', // Executar sem sandbox
            ]);
            
            $capabilities = DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options);
            $driver = RemoteWebDriver::create('http://localhost:9515', $capabilities); 

            $driver->get('https://testpages.herokuapp.com/styled/file-upload-test.html');

            $filePath = 'C:\Users\Usuario\Downloads\TesteTKS';
            $fileInput = $driver->findElement(WebDriverBy::name('filename'));        
            $fileInput->sendKeys($filePath);

            // Fechar o WebDriver
            //$driver->quit();

            return response()->json(['success' => 'Upload com sucesso!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage(), 'message' => 'Falhou!']);
        }
    }   
}





    
    
