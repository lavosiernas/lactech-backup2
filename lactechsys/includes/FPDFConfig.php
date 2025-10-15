<?php
/**
 * Configurações para FPDF
 * Extensão da classe FPDF para suporte a transparência e outras funcionalidades
 */

class FPDF extends FPDF {
    
    protected $extgstates = array();
    
    /**
     * Definir estado gráfico com transparência
     */
    function SetAlpha($alpha, $bm = 'Normal') {
        $gs = $this->AddExtGState(array('ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm));
        $this->SetExtGState($gs);
    }
    
    /**
     * Adicionar estado gráfico estendido
     */
    function AddExtGState($parms) {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }
    
    /**
     * Definir estado gráfico estendido
     */
    function SetExtGState($gs) {
        $this->_out(sprintf('/GS%d gs', $gs));
    }
    
    /**
     * Salvar estado gráfico
     */
    function saveGraphicsState() {
        $this->_out('q');
    }
    
    /**
     * Restaurar estado gráfico
     */
    function restoreGraphicsState() {
        $this->_out('Q');
    }
    
    /**
     * Rotacionar página ou elemento
     */
    function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1) {
            $x = $this->x;
        }
        if ($y == -1) {
            $y = $this->y;
        }
        if ($this->angle != 0) {
            $this->_out('Q');
        }
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }
    
    /**
     * Iniciar transformação
     */
    function StartTransform() {
        $this->Rotate(0);
    }
    
    /**
     * Parar transformação
     */
    function StopTransform() {
        $this->Rotate(0);
    }
    
    /**
     * Sobrescrever _endpage para incluir estados gráficos estendidos
     */
    function _endpage() {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }
    
    /**
     * Sobrescrever _putextgstates para incluir transparência
     */
    function _putextgstates() {
        for ($i = 1; $i <= count($this->extgstates); $i++) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            $parms = $this->extgstates[$i]['parms'];
            $this->_out(sprintf('/ca %.3F', $parms['ca']));
            $this->_out(sprintf('/CA %.3F', $parms['CA']));
            $this->_out('/BM ' . $parms['BM']);
            $this->_out('>>');
            $this->_out('endobj');
        }
    }
    
    /**
     * Sobrescrever _putresourcedict para incluir recursos de transparência
     */
    function _putresourcedict() {
        parent::_putresourcedict();
        $this->_out('/ExtGState <<');
        foreach ($this->extgstates as $i => $extgstate) {
            $this->_out('/GS' . $i . ' ' . $extgstate['n'] . ' 0 R');
        }
        $this->_out('>>');
    }
    
    /**
     * Sobrescrever _putresources para incluir estados gráficos estendidos
     */
    function _putresources() {
        $this->_putextgstates();
        parent::_putresources();
    }
}
?>









