
<?php
function validateUserAccess($config, $id = null)
{
    // global $userid;
    // if (!$userid) {
    //     sendUnauthorizedResponse("User not authenticated.");
    // }
    // // Add additional logic to validate access
    return $config;
}

function validateAndAssignUser($config, $data)
{
    // global $userid;
    // if (!$userid) {
    //     sendUnauthorizedResponse("User not authenticated.");
    // }
    // $data['user_id'] = $userid; // Automatically assign user ID
    return [$config, $data];
}

function validateReviewData($config, $data)
{
    // if (empty($data['review'])) {
    //     sendBadRequestResponse("Review content cannot be empty.");
    // }
    return [$config, $data];
}

function validateJobAdvertData($config, $data)
{
    // if (empty($data['title']) || empty($data['company'])) {
    //     sendBadRequestResponse("Title and Company are required.");
    // }
    return [$config, $data];
}
