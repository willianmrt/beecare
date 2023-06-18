<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PDFController extends Controller
{

/* Modelo de utilização:
 * # <?php
 * # $var = new PdfHandler ("arquivo.pdf");
 * # echo $var->pdfText;
 * # ?>
 *
 * O output ainda não foi planejado, portanto não foi implementado.
 * Para ver o conteúdo bruto do arquivo, descomente a linha 147; já
 * para ver o conteúdo da array de obj's, descomente a linha 138.
 */

    /* Nome do arquivo PDF.
     * @var string
     */
    private $_fileName = "";

    /* Manipulador do arquivo.
     * @var integer
     */
    private $_fileHandle = "";

    /* Buffer temporário para leitura das linhas do arquivo.
     * @var string
     */
    private $_fileBuffer = "";

    /* Versão do arquivo.
     * @var string
     */
    private $_fileVersion = "";

    /* Título do arquivo.
     * @var string
     */
    private $_pdfTitle = "";

    /* Assunto do arquivo.
     * @var string
     */
    private $_pdfSubject = "";

    /* Palavras-chave do arquivo.
     * @var string
     */
    private $_pdfKeywords = "";

    /* Autor do arquivo.
     * @var string
     */
    private $_pdfAuthor = "";

    /* Nome do software que criou o arquivo PDF.
     * @var string
     */
    private $_pdfProducer = "";

    /* Data de criação do arquivo.
     * @var string
     */
    private $_pdfCreationDate = "";

    /* Data da última modificação no arquivo.
     * @var string
     */
    private $_pdfLastModificationDate = "";

    /* Número de páginas no arquivo.
     * @var integer
     */
    private $_pdfPageNumber = 0;

    /* Arranjo de objetos do arquivo.
     * @var array de string
     */
    private $_pdfObjects = array ();

    /* Texto contido no arquivo PDF.
     * @var string
     */
    public $pdfText = "";

    /* Construtor.
     * @param file (caminho para o arquivo PDF)
     */
    //public function __construct () {
    public function index() {
        /* Configuração para prevenir problemas entre arquivos criados em diferentes
         * plataformas: win32, mac/unix.
         */
        ini_set ('auto_detect_line_endings', true);
        $file = public_path('pdfs/LeituraPDF.pdf');

        $this->_fileName = $file;
        $this->_fileBuffer = '';
        $this->_fileHandle = fopen ($this->_fileName, 'r');

        if ($this->_fileHandle) {
            /* Regex para buscar os quatro principais blocos do arquivo PDF.
             */
            $regExPattern = array (      
                'header'  => "/^%PDF-(\d+\.\d+)$/",
                'obj'     => "/^(?P<id>\d+) (\d+) obj\s*(<<.*>>)*(stream)*/",
                'xref'    => "/^(xref)$/",
                'trailer' => "/^(trailer)$/"
            );
            /* Laço que irá percorrer todos o arquivo linha por linha.
             */
            while ($this->_getLine ()) {
                /* Captura a versão em que foi criado o arquivo.
                 */
                if ($this->_fileVersion == "") {
                    if (preg_match ($regExPattern['header'], trim ($this->_fileBuffer), $matches)) {
                        $this->_fileVersion = $matches[1];
                        continue;
                    }
                }
                /* Captura um objeto completo a cada laço.
                 */
                elseif (preg_match ($regExPattern['obj'], trim ($this->_fileBuffer), $matches)) {
                    $this->_getObj (floor ($matches['id']));
                }
                /* Captura o bloco XREF do arquivo.
                 */
                elseif (preg_match ($regExPattern['xref'], trim ($this->_fileBuffer), $matches)) {
                     $this->_getXref ();
                }
                /* Captura o bloco TRAILER do arquivo.
                 */
                elseif (preg_match ($regExPattern['trailer'], trim ($this->_fileBuffer), $matches)) {
                    $this->_getTrailer ();
                }

            }
            fclose ($this->_fileHandle);
        }
        //print_r($this->_pdfObjects);
    }

    /* Método que lê cada linha do arquivo temporariamente para o buffer.
     * @return boolean
     */
    private function _getLine () {
        if (!feof ($this->_fileHandle)) {
            $this->_fileBuffer = fgets ($this->_fileHandle);
            echo $this->_fileBuffer;
            return true;
        }
        return false;
    }

    /* Lê um objeto completo por vez para a array de objetos.
     * @return string
     */
    private function _getObj ($id) {
        $buffer  = "";
        $sLength = 0;
        $pos     = array ('objStart'    => 0,
                          'objEnd'      => 0,
                          'dicStart'    => 0,
                          'dicEnd'      => 0,
                          'streamStart' => 0,
                          'streamEnd'   => 0);
        /* Lê todas as linhas até encontrar o final do objeto para a variável
         * local $buffer para então ter o conteúdo tratado.
         */
        do {
            /* Concatena as linhas do arquivo em um string constante. O conteúdo
               do bloco STREAM não passa por TRIM.
             */
            if (preg_match ("/^stream$/", trim ($this->_fileBuffer))) {
                while (preg_match ("/^endstream$/", trim ($this->_fileBuffer)) != 1) {
                    $buffer .= $this->_fileBuffer;
                    $this->_getLine ();
                }
            }
            /* O restante é concatenado com TRIM, para facilitar o manuseio.
             */
            $buffer .= trim ($this->_fileBuffer);
            if (preg_match ("/^endobj$/", trim ($this->_fileBuffer))) {
                break;
            }
        } while ($this->_getLine ());
        /* Stream de conteúdos diversos, que não são texto.
         */
        if (strpos($buffer, "/Device", 0)     !== false ||
            strpos($buffer, "/Image", 0)      !== false ||
            strpos($buffer, "/Metadata", 0)   !== false ||
            strpos($buffer, "/ColorSpace", 0) !== false) {
            return;
        }
        /* Codificações a serem ignoradas por falta de suporte (poderão ser
         * incluídas no futuro).
         */
        if (strpos($buffer, "/LZWDecode", 0)       !== false ||
            strpos($buffer, "/RunLengthDecode", 0) !== false ||
            strpos($buffer, "/CCITTFaxDecode", 0)  !== false ||
            strpos($buffer, "/DCTDecode", 0)       !== false) {
            return;
        }
        /* Ignorar HINT TABLES.
         */
        if (preg_match("/\/[LS]\s{0,1}\d+/", $buffer) ||
            preg_match("/\/Length[123]\s{0,1}(\d+)/", $buffer)) {
            return;
        }
        /* Capturar as posições de início e fim de determinados subblocos.
         */
        $pos['objStart'] = strpos ($buffer, "obj", 0) + 3;
        $pos['objEnd']   = strpos ($buffer, "endobj", $pos['objStart']);

        $pos['dicStart'] = strpos ($buffer, "<<", $pos['objStart']) + 2;
        $pos['dicEnd']   = strpos ($buffer, ">>endobj", $pos['dicStart']);
        $pos['dicEnd']   = ($pos['dicEnd'] === false) ? strpos ($buffer, ">>stream", $pos['dicStart']) : $pos['dicEnd'];

        $pos['streamStart'] = strpos ($buffer, "stream", $pos['dicEnd']) + strlen ("stream");
        $pos['streamEnd']   = strpos ($buffer, "endstream", $pos['streamStart']);

        /* Separação de objeto quando o bloco de dicionário é encontrado.
         */
        if ($pos['dicStart'] !== false &&
            $pos['dicEnd']   !== false) {
            $this->_pdfObjects[$id]['dic'] = trim (substr ($buffer, $pos['dicStart'], $pos['dicEnd'] - $pos['dicStart']));
            /* Tratamento do bloco quando é encontrado o sub-bloco stream.
             */
            if ($pos['streamStart'] !== false &&
                $pos['streamEnd']   !== false) {
                /* Decodificação do filtro FlateDecode.
                 */
                if (strpos ($this->_pdfObjects[$id]['dic'], "/FlateDecode", 0) !== false) {
                    $this->_pdfObjects[$id]['content'] = @gzuncompress (trim (substr($buffer, $pos['streamStart'], $pos['streamEnd'] - $pos['streamStart'])));
                }
                /* Decodificação do filtro ASCIIHexDecode.
                 */
                elseif (strpos ($this->_pdfObjects[$id]['dic'], "/ASCIIHexDecode", 0) !== false) {
                    $this->_pdfObjects[$id]['content'] = $this->_ASCIIHexDecode (trim (substr($buffer, $pos['streamStart'], $pos['streamEnd'] - $pos['streamStart'])));
                }
                /* Decodificação do filtro ASCII85Decode.
                 */
                elseif (strpos ($this->_pdfObjects[$id]['dic'], "/ASCII85Decode", 0)  !== false) {
                    $this->_pdfObjects[$id]['content'] = $this->_ASCII85Decode (trim (substr($buffer, $pos['streamStart'], $pos['streamEnd'] - $pos['streamStart'])));
                }
            }
            /* Caso não haja stream, o conteúdo será todo copiado sem tratamento.
             * Provavelmente seja uma referência cruzada, um texto plano ou
             * simplesmente nada (nos casos em que o conteúdo relevante fica
             * apenas no dicionário do objeto).
             */
            else {
                $this->_pdfObjects[$id]['content'] = trim (substr($buffer, $pos['dicEnd'], $pos['objEnd'] - $pos['dicEnd']));
            }
        }
        /* Da mesma forma, quando não há dicionário, pode se tratar de um valor
         * de em uma referência cruzada.
         */
        else {
            $this->_pdfObjects[$id]['dic'] = "";
            $this->_pdfObjects[$id]['content'] = trim (substr($buffer, $pos['objStart'], $pos['objEnd'] - $pos['objStart']));
        }
    }

    /* Trata e retorna o conteúdo do bloco XREF do arquivo PDF.
     * @return string
     */
    private function _getXref () {
        //TODO
    }

    /* Trata e retorna o conteúdo do bloco TRAILER do arquivo PDF.
     * @return string
     */
    private function _getTrailer () {
        //TODO
    }

    /* Decodes ASCIIHexDecode stream
     * @param string coded stream
     * @return string decoded stream
     */
     private function _ASCIIHexDecode ($input) {
        $output = "";

        $isOdd     = true;
        $isComment = false;

        for($i = 0, $codeHigh = -1; $i < strlen($input) && $input[$i] != '>'; $i++) {
            $c = $input[$i];
    
            if($isComment) {
                if ($c == '\r' ||
                    $c == '\n') {
                    $isComment = false;
                }
                continue;
            }
    
            switch($c) {
                case '': case '\t': case '\r': case '\f': case '\n': case ' ': break;
                case '%': 
                    $isComment = true;
                break;
    
                default:
                    $code = hexdec($c);
                    if($code === 0 && $c != '0') {
                        return "";
                    }
    
                    if($isOdd) {
                        $codeHigh = $code;
                    }
                    else {
                        $output .= chr($codeHigh * 16 + $code);
                    }
                    $isOdd = !$isOdd;
                break;
            }
        }

        if($input[$i] != '>') {
            return "";
        }
    
        if($isOdd) {
            $output .= chr($codeHigh * 16);
        }
        return $output;
     }

    /* Decodes ASCII85Decode stream
     * @param string coded stream
     * @return string decoded stream
     */
     private function _ASCII85Decode ($input) {
        $output = "";
    
        $isComment = false;
        $ords = array();
        
        for($i = 0, $state = 0; $i < strlen($input) && $input[$i] != '~'; $i++) {
            $c = $input[$i];

            if($isComment) {
                if ($c == '\r' || $c == '\n') {
                    $isComment = false;
                }
                continue;
            }
    
            if ($c == '' ||
                $c == '\t' ||
                $c == '\r' ||
                $c == '\f' ||
                $c == '\n' ||
                $c == ' ') {
                continue;
            }
            if ($c == '%') {
                $isComment = true;
                continue;
            }
            if ($c == 'z' &&
                $state === 0) {
                $output .= str_repeat(chr(0), 4);
                continue;
            }
            if ($c < '!' || $c > 'u') {
                return "";
            }
    
            $code = ord($input[$i]) & 0xff;
            $ords[$state++] = $code - ord('!');
    
            if ($state == 5) {
                $state = 0;
                for ($sum = 0, $j = 0; $j < 5; $j++) {
                    $sum = $sum * 85 + $ords[$j];
                }
                for ($j = 3; $j >= 0; $j--) {
                    $output .= chr($sum >> ($j * 8));
                }
            }
        }
        if ($state === 1) {
            return "";
        }
        elseif ($state > 1) {
            for ($i = 0, $sum = 0; $i < $state; $i++) {
                $sum += ($ords[$i] + ($i == $state - 1)) * pow(85, 4 - $i);
            }
            for ($i = 0; $i < $state - 1; $i++) {
                $ouput .= chr($sum >> ((3 - $i) * 8));
            }
        }
        return $output;
    }

    /* Converte hexadecimal para texto.
     * @param string hexadecimal string
     * @return string texto
     */
    public function hextostr($x) { 
        $s=''; 
        foreach(explode("\n",trim(chunk_split($x,2))) as $h) $s.=chr(hexdec($h)); 
        return($s); 
    }

    /* getters*/

    /* Título do documento.
     * @return string
     */
    public function getTitle () {
        return $this->_pdfTitle;  
    }

    /* Assunto do documento.
     * @return string
     */
    public function getSubject () {
        return $this->_pdfSubject;
    }

    /* Palavras-chave.
     * @return string
     */
    public function getKeywords () {
        return $this->_pdfKeywords;
    }

    /* Nome do autor.
     * @return string
     */
    public function getAuthor () {
        return $this->_pdfAuthor;
    }

    /* Nome do aplicativo que gerou o documento.
     * @return string
     */
    public function getProducer () {
        return $this->_pdfProducer;
    }

    /* Data e hora da criação do documento.
     * @return string
     */
    public function getCreationDate () {
        return $this->_pdfCreationDate;
    }

    /* Data e hora da última alteração no documento.
     * @return string
     */
    public function getLastModificationDate () {
        return $this->_pdfLastModificationDate;
    }
}

