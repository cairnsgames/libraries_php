<?php

function checkAndAllocateReward($mysqli, $appId, $user_id, $system_id) {
    // Check the number of stamps required for the reward
    $query = "SELECT stamps_required, reward_description FROM loyalty_system WHERE id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('i', $system_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ["success" => false, "message" => "System not found"];
    }
    
    $system = $result->fetch_assoc();
    $stamps_required = $system['stamps_required'] ? $system['stamps_required'] : 10; // Default to 10 if not set
    $reward_description = $system['reward_description'];
    $stmt->close();

    // Check the user's current stamp count
    $query = "SELECT id, stamps_collected FROM loyalty_card WHERE user_id = ? AND system_id = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('ii', $user_id, $system_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ["success" => false, "message" => "Loyalty card not found"];
    }
    
    $card = $result->fetch_assoc();
    $card_id = $card['id'];
    $stamps_collected = $card['stamps_collected'];
    $stmt->close();

    // Check if user has enough stamps for a reward
    if ($stamps_collected >= $stamps_required) {
        // Reset stamps to zero
        $query = "UPDATE loyalty_card SET stamps_collected = 0, date_modified = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('i', $card_id);
        $stmt->execute();
        $stmt->close();

        // Add reward to loyalty_reward table
        $query = "INSERT INTO loyalty_reward (app_id, user_id, system_id, reward_description, date_earned, date_created, date_modified) 
                  VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param('siis', $appId, $user_id, $system_id, $reward_description);
        $stmt->execute();
        $stmt->close();
        
        return ["success" => true, "message" => "Reward allocated and stamps reset to zero."];
    }

    return ["success" => false, "message" => "Not enough stamps for a reward."];
}
