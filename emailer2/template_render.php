<?php

function render($app_id, $template_name, $data = [], $lang = "en") {
    $template = getTemplateContent((string)$app_id, $template_name, $lang);
    if ($template === false) {
        http_response_code(404);
        return "Template not found.";
    }
    $template_content = $template['content'];
    $template_subject = $template['subject'];
    $properties = getEmailProperties((string)$app_id);

    // Merge properties under 'property' key
    $data_with_props = $data;
    $data_with_props['property'] = $properties;

    // Render the template
    $template["content"] = renderTemplate($template_content, $data_with_props, $properties, $lang, (string)$app_id);
    $template["subject"] = renderTemplate($template_subject, $data_with_props, $properties, $lang, (string)$app_id);
    return $template;
}
