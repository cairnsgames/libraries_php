<?php
require_once '../utils.php';

/**
 * Load main template content by app_id and template name or ID.
 * @param string $app_id
 * @param string|int $template_name_or_id
 * @param string $lang Language code, defaults to "en"
 * @return array|false [ 'subject' => string, 'content' => string ]|false
 */
function getTemplateContent(string $app_id, $template_name_or_id, $lang = "en")
{
    if (is_numeric($template_name_or_id)) {
        $sql = "SELECT subject, content FROM email_templates WHERE app_id = ? AND id = ? AND lang = ? LIMIT 1";
        $params = [$app_id, (int)$template_name_or_id, $lang];
    } else {
        $sql = "SELECT subject, content FROM email_templates WHERE app_id = ? AND name = ? AND lang = ? LIMIT 1";
        $params = [$app_id, $template_name_or_id, $lang];
    }

    $result = executeSQL($sql, $params);
    if (!empty($result)) {
        return ["subject" => $result[0]['subject'], "content" => $result[0]['content']];
    }

    // If not found and lang is not "en", retry with "en"
    if ($lang !== "en") {
        if (is_numeric($template_name_or_id)) {
            $params = [$app_id, (int)$template_name_or_id, "en"];
        } else {
            $params = [$app_id, $template_name_or_id, "en"];
        }
        $result = executeSQL($sql, $params);
        if (!empty($result)) {
            return ["subject" => $result[0]['subject'], "content" => $result[0]['content']];
        }
    }

    return false;
}

/**
 * Load part content by app_id and part name.
 * @param string $app_id
 * @param string $part_name
 * @param string $lang Language code, defaults to "en"
 * @return string|false
 */
function getTemplatePartContent(string $app_id, string $part_name, $lang = "en")
{
    $sql = "SELECT content FROM email_template_parts WHERE app_id = ? AND name = ? AND lang = ? LIMIT 1";
    $params = [$app_id, $part_name, $lang];
    $result = executeSQL($sql, $params);
    if (!empty($result)) {
        return $result[0]['content'];
    }
    // If not found and lang is not "en", retry with "en"
    if ($lang !== "en") {
        $params = [$app_id, $part_name, "en"];
        $result = executeSQL($sql, $params);
        if (!empty($result)) {
            return $result[0]['content'];
        }
    }
    return false;
}

/**
 * Load all email properties for app as assoc array.
 * @param string $app_id
 * @return array [property_key => property_value]
 */
function getEmailProperties(string $app_id): array
{
    $sql = "SELECT property_key, property_value, lang FROM email_properties WHERE app_id = ?";
    $params = [$app_id];
    $result = executeSQL($sql, $params);
    $props = [];
    foreach ($result as $row) {
        $props[$row['property_key']] = $row['property_value'];
    }
    return $props;
}

/**
 * Core renderer: resolve parts, loops, placeholders.
 * @param string $template_content
 * @param array $data
 * @param array $properties
 * @param string $lang Language code, defaults to "en"
 * @param string $app_id
 * @return string
 */
function renderTemplate(string $template_content, array $data, array $properties, $lang, string $app_id): string
{
    // Merge properties under 'property' key
    $data_with_props = $data;
    $data_with_props['property'] = $properties;

    // 1. Replace parts recursively
    $content = replaceParts($template_content, $app_id, $lang, $data_with_props, $properties);

    // 1.5. Process conditionals ({{#if ...}}{{#else}}{{/if}})
    $content = processConditionals($content, $data_with_props, $lang, $app_id, $properties);

    // 2. Process loops
    $content = processLoops($content, $data_with_props, $lang);

    // 3. Replace placeholders
    $content = replacePlaceholders($content, $data_with_props, $lang);

    return $content;
}

/**
 * Replace all {{part.part_name}} tags recursively with their rendered content.
 * @param string $content
 * @param string $app_id
 * @param string $lang 
 * @param array $data
 * @param array $properties
 * @return string
 */
function replaceParts(string $content, string $app_id, string $lang, array $data, array $properties): string
{
    // Regex to match {{part.part_name}}
    $pattern = '/\{\{\s*part\.([a-zA-Z0-9_\-]+)\s*\}\}/';

    return preg_replace_callback($pattern, function ($matches) use ($app_id, $data, $properties, $lang) {
        $part_name = $matches[1];
        $part_content = getTemplatePartContent($app_id, $part_name, $lang);
        if ($part_content === false) {
            // Part not found, replace with empty string or leave tag as-is? Here empty.
            return '';
        }
        // Render part content recursively (parts may contain other parts, loops, placeholders)
        return renderTemplate($part_content, $data, $properties, $lang, $app_id);
    }, $content);
}

/**
 * Process loops of format:
 * {{#each items as item}}
 *   ... {{item.property}} ...
 * {{/each}}
 * @param string $content
 * @param array $data
 * @return string
 */
function processLoops(string $content, array $data, string $lang = "en"): string
{
    // Regex pattern to find each loops
    $pattern = '/\{\{\#each\s+([a-zA-Z0-9_\.\-]+)\s+as\s+([a-zA-Z0-9_\-]+)\s*\}\}(.*?)\{\{\/each\}\}/s';

    return preg_replace_callback($pattern, function ($matches) use ($data, $lang) {
        $array_key = $matches[1];
        $item_name = $matches[2];
        $loop_block = $matches[3];

        $array_value = getValueFromData($data, $array_key);
        if (!is_array($array_value) || empty($array_value)) {
            // Empty or missing array - no output
            return '';
        }

        $result = '';
        foreach ($array_value as $item) {
            // For each item, we create a temporary data scope with the item accessible by $item_name
            // Merge item properties as keys in scope $item_name.*
            $scope = $data;
            $scope[$item_name] = $item;
            // Replace placeholders inside loop block with this scope
            $rendered_block = replacePlaceholders($loop_block, $scope, $lang);
            $result .= $rendered_block;
        }

        return $result;
    }, $content);
}

/**
 * Process conditional blocks of format:
 * {{#if condition}}
 *    ... content ...
 * {{#else}}
 *    ... else content ...
 * {{/if}}
 * Supports simple presence checks (e.g. `options`) and comparisons
 * like `option.value == "yes"` (double or single quoted) or numeric comparisons.
 * @param string $content
 * @param array $data
 * @param string $lang
 * @param string $app_id
 * @param array $properties
 * @return string
 */
function processConditionals(string $content, array $data, string $lang = "en", string $app_id = '', array $properties = []): string
{
    // Regex to find the outermost if blocks (non-greedy)
    $pattern = '/\{\{\#if\s+([^\}]+)\}\}(.*?)\{\{\/if\}\}/s';

    return preg_replace_callback($pattern, function ($matches) use ($data, $lang, $app_id, $properties) {
        $condition = trim($matches[1]);
        $block = $matches[2];

        // Split on {{#else}} if present
        $parts = preg_split('/\{\{\#else\}\}/', $block, 2);
        $trueBlock = $parts[0];
        $falseBlock = isset($parts[1]) ? $parts[1] : '';

        $result = evaluateCondition($condition, $data, $lang);

        // If condition true, keep trueBlock, otherwise falseBlock
        $chosen = $result ? $trueBlock : $falseBlock;

        // Process nested conditionals inside the chosen block
        $chosen = processConditionals($chosen, $data, $lang, $app_id, $properties);

        // We do not run loops/placeholders here; renderTemplate will handle them next.
        return $chosen;
    }, $content);
}

/**
 * Evaluate a simple condition string against $data.
 * Supports:
 * - Presence/truthiness: `options`
 * - Equality/inequality with quoted strings or numbers: `option.value == "yes"`
 * - Numeric comparisons: >, <, >=, <=
 * @param string $condition
 * @param array $data
 * @param string $lang
 * @return bool
 */
function evaluateCondition(string $condition, array $data, string $lang = "en"): bool
{
    // Try to parse binary comparisons first
    $cmpPattern = '/^\s*([a-zA-Z0-9_\.\-]+)\s*(==|!=|===|!==|>=|<=|>|<)\s*(?:"([^"]*)"|\'([^\']*)\'|([0-9]+(?:\.[0-9]+)?))\s*$/';
    if (preg_match($cmpPattern, $condition, $m)) {
        $leftKey = $m[1];
        $op = $m[2];
        $right = null;
        if (isset($m[3]) && $m[3] !== '') $right = $m[3];
        elseif (isset($m[4]) && $m[4] !== '') $right = $m[4];
        elseif (isset($m[5]) && $m[5] !== '') $right = $m[5];

        $leftVal = getValueFromData($data, $leftKey, $lang);

        // Convert arrays/objects to string for comparison
        if (is_array($leftVal)) {
            $leftVal = json_encode($leftVal);
        }

        // If right looks numeric, compare as numbers when possible
        $isRightNumeric = is_numeric($right);
        if ($isRightNumeric) {
            $leftNum = is_numeric($leftVal) ? (float)$leftVal : null;
            $rightNum = (float)$right;
        }

        switch ($op) {
            case '==':
            case '===':
                if ($isRightNumeric && is_numeric($leftVal)) return $leftNum == $rightNum;
                return (string)$leftVal === (string)$right;
            case '!=':
            case '!==':
                if ($isRightNumeric && is_numeric($leftVal)) return $leftNum != $rightNum;
                return (string)$leftVal !== (string)$right;
            case '>':
                if ($isRightNumeric && is_numeric($leftVal)) return (float)$leftVal > (float)$right;
                return (string)$leftVal > (string)$right;
            case '<':
                if ($isRightNumeric && is_numeric($leftVal)) return (float)$leftVal < (float)$right;
                return (string)$leftVal < (string)$right;
            case '>=':
                if ($isRightNumeric && is_numeric($leftVal)) return (float)$leftVal >= (float)$right;
                return (string)$leftVal >= (string)$right;
            case '<=':
                if ($isRightNumeric && is_numeric($leftVal)) return (float)$leftVal <= (float)$right;
                return (string)$leftVal <= (string)$right;
        }
    }

    // Fallback: treat condition as presence/truthiness of key in $data
    $val = getValueFromData($data, $condition, $lang);
    if ($val === null || $val === '' || $val === false) return false;
    if (is_array($val)) return !empty($val);
    return (bool)$val;
}

/**
 * Replace all {{placeholder}} with corresponding values in $data.
 * Supports dot notation.
 * @param string $content
 * @param array $data
 * @param string $lang Language code
 * @return string
 */
function replacePlaceholders(string $content, array $data, $lang): string
{
    $pattern = '/\{\{\s*([a-zA-Z0-9_\.\-]+)\s*\}\}/';

    return preg_replace_callback($pattern, function ($matches) use ($data, $lang) {
        $key = $matches[1];
        $value = getValueFromData($data, $key, $lang);
        return sanitizeForHtml((string)$value);
    }, $content);
}

/**
 * Get nested value from data array using dot notation key.
 * Returns empty string if not found.
 * @param array $data
 * @param string $key
 * @param string $lang Language code
 * @return mixed
 */
function getValueFromData(array $data, string $key, string $lang = "en"): mixed
{
    $keys = explode('.', $key);
    $current = $data;
    foreach ($keys as $k) {
        // If current is an array of arrays with 'name' and 'lang', find matching element
        if (is_array($current) && isset($current[0]) && is_array($current[0]) && isset($current[0]['name'], $current[0]['lang'])) {
            $found = null;
            foreach ($current as $item) {
                if (isset($item['name'], $item['lang']) && $item['name'] === $k && $item['lang'] === $lang) {
                    $found = $item;
                    break;
                }
            }
            // Fallback to 'en' if not found in $lang
            if (!$found && $lang !== "en") {
                foreach ($current as $item) {
                    if (isset($item['name'], $item['lang']) && $item['name'] === $k && $item['lang'] === "en") {
                        $found = $item;
                        break;
                    }
                }
            }
            if ($found) {
                $current = $found;
            } else {
                return '';
            }
        } elseif (is_array($current) && array_key_exists($k, $current)) {
            $current = $current[$k];
        } else {
            return '';
        }
    }
    return $current;
}

/**
 * Sanitize string for safe HTML output.
 * @param string $value
 * @return string
 */
function sanitizeForHtml(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Log sent email in email_logs table.
 * @param string $app_id
 * @param int $template_id
 * @param string $recipient_email
 * @param string|null $subject
 * @param array $data
 * @return bool
 */
function logEmailSent(string $app_id, int $template_id, string $recipient_email, ?string $subject, array $data, string $lang = "en"): bool
{
    $sql = "INSERT INTO email_logs (app_id, template_id, recipient_email, subject, data, lang) VALUES (?, ?, ?, ?, ?, ?)";
    $params = [
        $app_id,
        $template_id,
        $recipient_email,
        $subject,
        json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ];

    try {
        executeSQL($sql, $params);
        return true;
    } catch (Exception $e) {
        // Log error as needed
        return false;
    }
}
