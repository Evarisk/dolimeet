<?php

// Action to set status STATUS_LOCKED
if ($action == 'confirm_lock' && $permissiontoadd) {
    $object->fetch($id);
    if (!$error) {
        $result = $object->setLocked($user, false);
        if ($result > 0) {
            // Set locked OK
            $urltogo = str_replace('__ID__', $result, $backtopage);
            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
            header("Location: " . $urltogo);
            exit;
        } elseif (!empty($object->errors)) { // Set locked KO
            setEventMessages('', $object->errors, 'errors');
        } else {
            setEventMessages($object->error, [], 'errors');
        }
    }
}

// Action to set status STATUS_ARCHIVED
if ($action == 'confirm_archive' && $permissiontoadd) {
    $object->fetch($id);
    if (!$error) {
        $result = $object->setArchived($user);
        if ($result > 0) {
            // Set Archived OK
            $urltogo = str_replace('__ID__', $result, $backtopage);
            $urltogo = preg_replace('/--IDFORBACKTOPAGE--/', $id, $urltogo); // New method to autoselect project after a New on another form object creation
            header('Location: ' . $urltogo);
            exit;
        } elseif (!empty($object->errors)) { // Set Archived KO
            setEventMessages('', $object->errors, 'errors');
        } else {
            setEventMessages($object->error, [], 'errors');
        }
    }
}