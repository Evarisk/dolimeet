<?php

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
    print "Error, template page can't be called as URL";
    exit;
}

?>

<div class="wpeo-dropdown">
    <div class="dropdown-toggle wpeo-button button-main"><span><?php echo $langs->trans('SignatureActions'); ?></span> <i class="fas fa-caret-down"></i></div>
    <ul class="dropdown-content">
        <?php if ($object->status < $object::STATUS_LOCKED) {
            if (empty($element->signature) && $element->status != $element::STATUS_ABSENT) {
                print '<li class="dropdown-item">';
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="set_absent">';
                print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
                print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                print '<button type="submit" class="signature-absent wpeo-button button-primary" value="' . $element->id . '">';
                print '<span>' . $langs->trans('Absent') . '</span>';
                print '</button>';
                print '</form>';
                print '</li>';
            }
            if ($object->status > $object::STATUS_DRAFT) {
                print '<li class="dropdown-item">';
                print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
                print '<input type="hidden" name="token" value="' . newToken() . '">';
                print '<input type="hidden" name="action" value="send">';
                print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
                print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
                print '<button type="submit" class="signature-email wpeo-button button-primary" value="' . $element->id . '">';
                print '<span><i class="fas fa-at"></i> ' . $langs->trans('SendEmail') . '</span>';
                print '</button>';
                print '</form>';
                print '</li>';
            }
        }
        if ($object->status < $object::STATUS_LOCKED) {
            print '<li class="dropdown-item">';
            print '<form method="POST" action="' . $_SERVER['PHP_SELF'] . '?id=' . $id . '&module_name=' . $moduleName . '&object_type=' . $object->element . '">';
            print '<input type="hidden" name="token" value="' . newToken() . '">';
            print '<input type="hidden" name="action" value="delete_attendant">';
            print '<input type="hidden" name="signatoryID" value="' . $element->id . '">';
            print '<input type="hidden" name="backtopage" value="' . $backtopage . '">';
            print '<button type="submit" name="deleteAttendant" id="deleteAttendant" class="attendant-delete wpeo-button button-primary" value="' . $element->id . '">';
            print '<span><i class="fas fa-trash"></i>' . $langs->trans('DeleteAttendant') . '</span>';
            print '</button>';
            print '</form>';
            print '</li>';
        } ?>
    </ul>
</div>