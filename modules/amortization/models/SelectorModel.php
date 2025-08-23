<?php
namespace Ksfraser\Amortizations;

class SelectorModel
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }
    public function getOptions($selector_name)
    {
        $stmt = $this->db->prepare("SELECT option_name, option_value FROM 0_ksf_selectors WHERE selector_name = ? ORDER BY option_name");
        $stmt->execute([$selector_name]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
