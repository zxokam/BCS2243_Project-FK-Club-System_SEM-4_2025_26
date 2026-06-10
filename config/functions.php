<?php
function clean($data) {
    return htmlspecialchars($data ?? "", ENT_QUOTES, "UTF-8");
}

function esc($conn, $data) {
    return mysqli_real_escape_string($conn, trim($data ?? ""));
}

function initials($name) {
    $words = explode(" ", trim($name));
    $result = "";
    foreach ($words as $word) {
        if ($word != "") {
            $result .= strtoupper(substr($word, 0, 1));
        }
        if (strlen($result) >= 2) {
            break;
        }
    }
    return $result == "" ? "FK" : $result;
}

function is_active($key, $current) {
    return $key == $current ? "active" : "";
}

function password_match($input, $stored) {
    return $input === $stored || password_verify($input, $stored);
}

function registration_summary($conn, $eventID) {
    $eventID = mysqli_real_escape_string($conn, $eventID);
    $summary = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT
            SUM(CASE WHEN registration_status = 'Registered' THEN 1 ELSE 0 END) AS registered,
            SUM(CASE WHEN registration_status = 'Waiting List' THEN 1 ELSE 0 END) AS waiting,
            SUM(CASE WHEN registration_status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled
        FROM event_registration
        WHERE eventID = '$eventID'
    "));

    return [
        "registered" => (int)($summary["registered"] ?? 0),
        "waiting" => (int)($summary["waiting"] ?? 0),
        "cancelled" => (int)($summary["cancelled"] ?? 0)
    ];
}

function resequence_waiting_list($conn, $eventID) {
    $eventID = mysqli_real_escape_string($conn, $eventID);
    $waiting = mysqli_query($conn, "
        SELECT registrationID
        FROM event_registration
        WHERE eventID = '$eventID'
        AND registration_status = 'Waiting List'
        ORDER BY queue_number ASC, registration_date ASC, registrationID ASC
    ");

    $queue = 1;
    while ($row = mysqli_fetch_assoc($waiting)) {
        $registrationID = mysqli_real_escape_string($conn, $row["registrationID"]);
        mysqli_query($conn, "UPDATE event_registration SET queue_number = '$queue' WHERE registrationID = '$registrationID'");
        $queue++;
    }
}

function promote_waiting_list($conn, $eventID) {
    $eventID = mysqli_real_escape_string($conn, $eventID);
    $event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT max_participants FROM event WHERE eventID = '$eventID' LIMIT 1"));

    if (!$event || (int)$event["max_participants"] <= 0) {
        resequence_waiting_list($conn, $eventID);
        return;
    }

    $max = (int)$event["max_participants"];
    $summary = registration_summary($conn, $eventID);
    $availableSlots = $max - $summary["registered"];

    while ($availableSlots > 0) {
        $next = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT registrationID
            FROM event_registration
            WHERE eventID = '$eventID'
            AND registration_status = 'Waiting List'
            ORDER BY queue_number ASC, registration_date ASC, registrationID ASC
            LIMIT 1
        "));

        if (!$next) {
            break;
        }

        $registrationID = mysqli_real_escape_string($conn, $next["registrationID"]);
        mysqli_query($conn, "
            UPDATE event_registration
            SET registration_status = 'Registered',
                confirmation_status = 'Confirmed',
                queue_number = 0
            WHERE registrationID = '$registrationID'
        ");

        $availableSlots--;
    }

    resequence_waiting_list($conn, $eventID);
}

function event_has_available_slot($conn, $eventID) {
    $eventID = mysqli_real_escape_string($conn, $eventID);
    $event = mysqli_fetch_assoc(mysqli_query($conn, "SELECT max_participants FROM event WHERE eventID = '$eventID' LIMIT 1"));

    if (!$event) {
        return false;
    }

    $max = (int)$event["max_participants"];
    if ($max <= 0) {
        return true;
    }

    $summary = registration_summary($conn, $eventID);
    return $summary["registered"] < $max;
}

function ensure_waiting_list_schema($conn) {
    // Keep this feature compatible with older localhost databases.
    // The ALTER statements are safe to run repeatedly because every change is checked first.
    $checkQueue = mysqli_query($conn, "SHOW COLUMNS FROM event_registration LIKE 'queue_number'");
    if ($checkQueue && mysqli_num_rows($checkQueue) == 0) {
        mysqli_query($conn, "ALTER TABLE event_registration ADD queue_number INT DEFAULT 0");
    }

    mysqli_query($conn, "ALTER TABLE event_registration MODIFY registration_status VARCHAR(30) NOT NULL DEFAULT 'Registered'");
    mysqli_query($conn, "ALTER TABLE event_registration MODIFY confirmation_status VARCHAR(30) NOT NULL DEFAULT 'Pending'");
    mysqli_query($conn, "ALTER TABLE event_registration MODIFY queue_number INT DEFAULT 0");

    $check = mysqli_query($conn, "SHOW COLUMNS FROM event LIKE 'waiting_list_status'");
    if ($check && mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "ALTER TABLE event ADD waiting_list_status VARCHAR(20) NOT NULL DEFAULT 'Closed'");
    }
}

function waiting_list_is_open($event) {
    return isset($event["waiting_list_status"]) && $event["waiting_list_status"] == "Open";
}

function update_waiting_list_status($conn, $eventID, $committeeID, $status) {
    $eventID = mysqli_real_escape_string($conn, $eventID);
    $committeeID = mysqli_real_escape_string($conn, $committeeID);
    $status = $status == "Open" ? "Open" : "Closed";

    return mysqli_query($conn, "
        UPDATE event
        SET waiting_list_status = '$status'
        WHERE eventID = '$eventID'
        AND created_by = '$committeeID'
    ");
}

function accept_waiting_registration($conn, $registrationID, $committeeID) {
    $registrationID = mysqli_real_escape_string($conn, $registrationID);
    $committeeID = mysqli_real_escape_string($conn, $committeeID);

    $registration = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT er.*, e.max_participants, e.created_by
        FROM event_registration er
        JOIN event e ON er.eventID = e.eventID
        WHERE er.registrationID = '$registrationID'
        AND e.created_by = '$committeeID'
        AND er.registration_status = 'Waiting List'
        LIMIT 1
    "));

    if (!$registration) {
        return false;
    }

    $eventID = mysqli_real_escape_string($conn, $registration["eventID"]);
    $summary = registration_summary($conn, $eventID);
    $max = (int)$registration["max_participants"];

    // If the event is already full, accepting one waiting-list student increases the capacity by one.
    // This keeps the participant count and max participant display consistent for presentation.
    if ($max > 0 && $summary["registered"] >= $max) {
        $newMax = $summary["registered"] + 1;
        mysqli_query($conn, "UPDATE event SET max_participants = '$newMax' WHERE eventID = '$eventID' AND created_by = '$committeeID'");
    }

    $ok = mysqli_query($conn, "
        UPDATE event_registration
        SET registration_status = 'Registered',
            confirmation_status = 'Confirmed',
            queue_number = 0
        WHERE registrationID = '$registrationID'
    ");

    resequence_waiting_list($conn, $eventID);
    return $ok;
}

function reject_waiting_registration($conn, $registrationID, $committeeID) {
    $registrationID = mysqli_real_escape_string($conn, $registrationID);
    $committeeID = mysqli_real_escape_string($conn, $committeeID);

    $registration = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT er.eventID
        FROM event_registration er
        JOIN event e ON er.eventID = e.eventID
        WHERE er.registrationID = '$registrationID'
        AND e.created_by = '$committeeID'
        AND er.registration_status = 'Waiting List'
        LIMIT 1
    "));

    if (!$registration) {
        return false;
    }

    $eventID = mysqli_real_escape_string($conn, $registration["eventID"]);
    $ok = mysqli_query($conn, "
        UPDATE event_registration
        SET registration_status = 'Rejected',
            confirmation_status = 'Rejected',
            queue_number = 0
        WHERE registrationID = '$registrationID'
    ");

    resequence_waiting_list($conn, $eventID);
    return $ok;
}

function reject_all_waiting_list($conn, $eventID, $committeeID) {
    $eventID = mysqli_real_escape_string($conn, $eventID);
    $committeeID = mysqli_real_escape_string($conn, $committeeID);

    $event = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT eventID
        FROM event
        WHERE eventID = '$eventID'
        AND created_by = '$committeeID'
        LIMIT 1
    "));

    if (!$event) {
        return false;
    }

    $ok = mysqli_query($conn, "
        UPDATE event_registration
        SET registration_status = 'Rejected',
            confirmation_status = 'Rejected',
            queue_number = 0
        WHERE eventID = '$eventID'
        AND registration_status = 'Waiting List'
    ");

    resequence_waiting_list($conn, $eventID);
    return $ok;
}

?>
