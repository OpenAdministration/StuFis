<?php
/**
 * Created by PhpStorm.
 * User: konsul
 * Date: 22.06.18
 * Time: 02:02
 */

abstract class Renderer{
    abstract public function render();
    
    protected function renderTable(array $header, array $groupedContent, array $escapeFunctions = []){
        
        if (count($header) > count($escapeFunctions)){
            $escapeFunctions = array_merge(
                $escapeFunctions,
                array_fill(0, count($header) - count($escapeFunctions), "htmlspecialchars")
            );
        }
        ?>
        <table class="table">
            <thead>
            <tr>
                <?php
                foreach ($header as $titel){
                    echo "<th>$titel</th>";
                }
                ?>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($groupedContent as $groupName => $rows){
                if (!is_int($groupName)){ ?>
                    <tr>
                        <th colspan="<?= count($header) ?>"><?php echo $groupName; ?></th>
                    </tr>
                <?php }
                foreach ($rows as $row){ ?>
                    <tr>
                        <?php foreach (array_values($row) as $id => $cellContent){
                            echo "<td>" . $escapeFunctions[$id]($cellContent) . "</td>";
                        } ?>
                    </tr>
                <?php } ?>
            <?php } ?>
            </tbody>
        </table>
    <?php }
    
    protected function renderHeadline($text, int $headlineNr = 1){
        echo "<h" . htmlspecialchars($headlineNr) . ">" . htmlspecialchars($text) . "</h" . htmlspecialchars($headlineNr) . ">";
    }
    
    protected function formatDateToMonthYear($dateString){
        return !empty($dateString) ? strftime("%b %G", strtotime($dateString)) : "";
    }
}