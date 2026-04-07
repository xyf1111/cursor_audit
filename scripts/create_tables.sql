CREATE DATABASE IF NOT EXISTS `cursor_audit`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `cursor_audit`;

CREATE TABLE IF NOT EXISTS `ai_audit_log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键 ID',
    `trace_id` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '链路追踪 ID',
    `machine_id` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '机器 ID',
    `user_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '用户名',
    `timestamp` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '事件时间戳',
    `event_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '请求事件类型',
    `prompt` MEDIUMTEXT NOT NULL COMMENT '用户提问内容',
    `file_path` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '文件路径',
    `project_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '项目名称',
    `model_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '模型名称',
    `input_tokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '输入 token',
    `output_tokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '输出 token',
    `response` MEDIUMTEXT NOT NULL COMMENT '回答内容',
    `response_event_type` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '回答事件类型',
    `response_status` VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT '回答状态',
    `response_at` DATETIME DEFAULT NULL COMMENT '回答时间',
    `request_finished_at` DATETIME DEFAULT NULL COMMENT '请求完成时间',
    `created_at` DATETIME NOT NULL COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    KEY `idx_trace_id` (`trace_id`),
    KEY `idx_machine_id` (`machine_id`),
    KEY `idx_user_name` (`user_name`),
    KEY `idx_event_type` (`event_type`),
    KEY `idx_project_name` (`project_name`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 审计日志表';

CREATE TABLE IF NOT EXISTS `ai_user_daily_stat` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键 ID',
    `stat_date` DATE NOT NULL COMMENT '统计日期',
    `user_name` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '用户名',
    `request_count` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '请求次数',
    `input_tokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '输入 token 总数',
    `output_tokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '输出 token 总数',
    `total_tokens` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '总 token',
    `first_used_at` DATETIME DEFAULT NULL COMMENT '首次使用时间',
    `last_used_at` DATETIME DEFAULT NULL COMMENT '最后使用时间',
    `created_at` DATETIME NOT NULL COMMENT '创建时间',
    `updated_at` DATETIME NOT NULL COMMENT '更新时间',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_stat_date_user_name` (`stat_date`, `user_name`),
    KEY `idx_user_name` (`user_name`),
    KEY `idx_updated_at` (`updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 用户每日统计表';
