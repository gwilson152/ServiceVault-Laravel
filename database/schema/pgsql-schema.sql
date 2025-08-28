--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Name: check_time_entry_ticket_account_consistency(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.check_time_entry_ticket_account_consistency() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
            BEGIN
                -- If ticket_id is provided, ensure it belongs to the same account
                IF NEW.ticket_id IS NOT NULL THEN
                    IF NOT EXISTS (
                        SELECT 1 FROM tickets 
                        WHERE tickets.id = NEW.ticket_id 
                        AND tickets.account_id = NEW.account_id
                    ) THEN
                        RAISE EXCEPTION 'Time entry ticket must belong to the same account as the time entry';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$;


--
-- Name: prevent_duplicate_billing(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.prevent_duplicate_billing() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
            BEGIN
                -- For time entries, prevent changing invoice_id if it's already set
                IF TG_TABLE_NAME = 'time_entries' THEN
                    IF OLD.invoice_id IS NOT NULL AND NEW.invoice_id IS NOT NULL AND OLD.invoice_id != NEW.invoice_id THEN
                        RAISE EXCEPTION 'Time entry % is already associated with invoice %. Cannot reassign to invoice %.', 
                            NEW.id, OLD.invoice_id, NEW.invoice_id;
                    END IF;
                END IF;

                -- For ticket addons, prevent changing invoice_id if it's already set
                IF TG_TABLE_NAME = 'ticket_addons' THEN
                    IF OLD.invoice_id IS NOT NULL AND NEW.invoice_id IS NOT NULL AND OLD.invoice_id != NEW.invoice_id THEN
                        RAISE EXCEPTION 'Ticket addon % is already associated with invoice %. Cannot reassign to invoice %.', 
                            NEW.id, OLD.invoice_id, NEW.invoice_id;
                    END IF;
                END IF;

                RETURN NEW;
            END;
            $$;


--
-- Name: validate_time_entry_ticket_account(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION public.validate_time_entry_ticket_account() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
            BEGIN
                IF NEW.ticket_id IS NOT NULL THEN
                    IF NOT EXISTS (
                        SELECT 1 FROM tickets 
                        WHERE id = NEW.ticket_id 
                        AND account_id = NEW.account_id
                    ) THEN
                        RAISE EXCEPTION 'Ticket must belong to the same account as the time entry';
                    END IF;
                END IF;
                RETURN NEW;
            END;
            $$;


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.accounts (
    id uuid NOT NULL,
    external_id character varying(255),
    name character varying(255) NOT NULL,
    account_type character varying(255) DEFAULT 'customer'::character varying NOT NULL,
    description text,
    contact_person character varying(255),
    email character varying(255),
    phone character varying(255),
    website character varying(255),
    address text,
    city character varying(255),
    state character varying(255),
    postal_code character varying(255),
    country character varying(255),
    billing_address text,
    billing_city character varying(255),
    billing_state character varying(255),
    billing_postal_code character varying(255),
    billing_country character varying(255),
    tax_id character varying(255),
    notes text,
    settings json,
    theme_settings json,
    tax_preferences json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT accounts_account_type_check CHECK (((account_type)::text = ANY ((ARRAY['customer'::character varying, 'prospect'::character varying, 'partner'::character varying, 'internal'::character varying])::text[])))
);


--
-- Name: activity_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.activity_log (
    id uuid NOT NULL,
    user_id uuid,
    event_type character varying(255) NOT NULL,
    entity_type character varying(255),
    entity_id uuid,
    description text NOT NULL,
    old_data json,
    new_data json,
    ip_address character varying(45),
    user_agent text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: addon_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.addon_templates (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    default_amount numeric(10,2) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: billing_rates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.billing_rates (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    rate numeric(10,2) NOT NULL,
    account_id uuid,
    user_id uuid,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: billing_schedules; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.billing_schedules (
    id uuid NOT NULL,
    account_id uuid NOT NULL,
    frequency character varying(255) NOT NULL,
    day_of_period integer DEFAULT 1 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_generated_at timestamp(0) without time zone,
    next_generation_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT billing_schedules_frequency_check CHECK (((frequency)::text = ANY ((ARRAY['weekly'::character varying, 'monthly'::character varying, 'quarterly'::character varying, 'annually'::character varying])::text[])))
);


--
-- Name: billing_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.billing_settings (
    id uuid NOT NULL,
    company_name character varying(255) NOT NULL,
    company_address text,
    tax_id character varying(255),
    invoice_prefix character varying(10) DEFAULT 'INV-'::character varying NOT NULL,
    next_invoice_number integer DEFAULT 1000 NOT NULL,
    default_payment_terms integer DEFAULT 30 NOT NULL,
    default_notes text,
    terms_and_conditions text,
    master_tax_rate numeric(5,4),
    master_tax_type character varying(255) DEFAULT 'percentage'::character varying NOT NULL,
    master_tax_name character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT billing_settings_master_tax_type_check CHECK (((master_tax_type)::text = ANY ((ARRAY['percentage'::character varying, 'fixed'::character varying])::text[])))
);


--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.categories (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    type character varying(255) DEFAULT 'general'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: custom_field_values; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.custom_field_values (
    id uuid NOT NULL,
    custom_field_id uuid NOT NULL,
    entity_id uuid NOT NULL,
    value text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: custom_fields; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.custom_fields (
    id uuid NOT NULL,
    entity_type character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    label character varying(255) NOT NULL,
    type character varying(255) NOT NULL,
    options json,
    is_required boolean DEFAULT false NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT custom_fields_type_check CHECK (((type)::text = ANY ((ARRAY['text'::character varying, 'textarea'::character varying, 'select'::character varying, 'multiselect'::character varying, 'checkbox'::character varying, 'date'::character varying, 'number'::character varying])::text[])))
);


--
-- Name: domain_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.domain_mappings (
    id uuid NOT NULL,
    domain character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    priority character varying(255) DEFAULT 'medium'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT domain_mappings_priority_check CHECK (((priority)::text = ANY ((ARRAY['high'::character varying, 'medium'::character varying, 'low'::character varying])::text[])))
);


--
-- Name: email_configs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_configs (
    id uuid NOT NULL,
    account_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'imap'::character varying NOT NULL,
    host character varying(255) NOT NULL,
    port integer DEFAULT 993 NOT NULL,
    encryption character varying(255) DEFAULT 'ssl'::character varying NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    last_checked_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT email_configs_encryption_check CHECK (((encryption)::text = ANY ((ARRAY['ssl'::character varying, 'tls'::character varying, 'none'::character varying])::text[]))),
    CONSTRAINT email_configs_type_check CHECK (((type)::text = ANY ((ARRAY['imap'::character varying, 'pop3'::character varying, 'exchange'::character varying])::text[])))
);


--
-- Name: email_domain_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_domain_mappings (
    id uuid NOT NULL,
    domain character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    sort_order integer DEFAULT 0 NOT NULL
);


--
-- Name: email_processing_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_processing_logs (
    id uuid NOT NULL,
    email_config_id uuid,
    email_id character varying(255) NOT NULL,
    direction character varying(255) DEFAULT 'incoming'::character varying NOT NULL,
    message_id character varying(255),
    from_address character varying(255) NOT NULL,
    to_addresses json NOT NULL,
    cc_addresses json,
    bcc_addresses json,
    subject character varying(255),
    in_reply_to character varying(255),
    "references" text,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    error_message text,
    ticket_id uuid,
    ticket_comment_id uuid,
    account_id uuid,
    user_id uuid,
    headers json,
    body text,
    parsed_body_text text,
    parsed_body_html text,
    raw_email_content text,
    attachments json,
    received_at timestamp(0) without time zone,
    created_new_ticket boolean DEFAULT false NOT NULL,
    processed_at timestamp(0) without time zone,
    processing_duration_ms integer,
    retry_count integer DEFAULT 0 NOT NULL,
    next_retry_at timestamp(0) without time zone,
    actions_taken json,
    error_details json,
    error_stack_trace text,
    is_command boolean DEFAULT false NOT NULL,
    command_type character varying(255),
    command_data json,
    command_status character varying(255),
    command_response text,
    commands_processed integer DEFAULT 0 NOT NULL,
    commands_executed_count integer DEFAULT 0 NOT NULL,
    commands_failed_count integer DEFAULT 0 NOT NULL,
    command_processing_success boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT email_processing_logs_command_status_check CHECK (((command_status)::text = ANY ((ARRAY['pending'::character varying, 'processed'::character varying, 'failed'::character varying])::text[]))),
    CONSTRAINT email_processing_logs_direction_check CHECK (((direction)::text = ANY ((ARRAY['incoming'::character varying, 'outgoing'::character varying])::text[]))),
    CONSTRAINT email_processing_logs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'processing'::character varying, 'processed'::character varying, 'failed'::character varying, 'ignored'::character varying, 'retry'::character varying])::text[])))
);


--
-- Name: email_system_config; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_system_config (
    id bigint NOT NULL,
    configuration_name character varying(255) DEFAULT 'Application Email Configuration'::character varying NOT NULL,
    incoming_enabled boolean DEFAULT false NOT NULL,
    incoming_provider character varying(255),
    incoming_host character varying(255),
    incoming_port integer,
    incoming_username character varying(255),
    incoming_password character varying(255),
    incoming_encryption character varying(255),
    incoming_folder character varying(255) DEFAULT 'INBOX'::character varying NOT NULL,
    incoming_settings json,
    outgoing_enabled boolean DEFAULT false NOT NULL,
    outgoing_provider character varying(255),
    outgoing_host character varying(255),
    outgoing_port integer,
    outgoing_username character varying(255),
    outgoing_password character varying(255),
    outgoing_encryption character varying(255),
    outgoing_settings json,
    from_address character varying(255),
    from_name character varying(255),
    reply_to_address character varying(255),
    system_active boolean DEFAULT false NOT NULL,
    enable_email_processing boolean DEFAULT true NOT NULL,
    last_tested_at timestamp(0) without time zone,
    test_results json,
    auto_create_tickets boolean DEFAULT true NOT NULL,
    auto_create_users boolean DEFAULT true NOT NULL,
    process_commands boolean DEFAULT true NOT NULL,
    send_confirmations boolean DEFAULT true NOT NULL,
    max_retries integer DEFAULT 3 NOT NULL,
    processing_rules json,
    unmapped_domain_strategy character varying(255) DEFAULT 'assign_default_account'::character varying NOT NULL,
    default_account_id character varying(255),
    default_role_template_id character varying(255),
    require_email_verification boolean DEFAULT true NOT NULL,
    require_admin_approval boolean DEFAULT true NOT NULL,
    timestamp_source character varying(255) DEFAULT 'original'::character varying NOT NULL,
    timestamp_timezone character varying(255) DEFAULT 'preserve'::character varying NOT NULL,
    updated_by_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT email_system_config_incoming_encryption_check CHECK (((incoming_encryption)::text = ANY ((ARRAY['tls'::character varying, 'ssl'::character varying, 'starttls'::character varying, 'none'::character varying])::text[]))),
    CONSTRAINT email_system_config_outgoing_encryption_check CHECK (((outgoing_encryption)::text = ANY ((ARRAY['tls'::character varying, 'ssl'::character varying, 'starttls'::character varying, 'none'::character varying])::text[]))),
    CONSTRAINT email_system_config_unmapped_domain_strategy_check CHECK (((unmapped_domain_strategy)::text = ANY ((ARRAY['create_account'::character varying, 'assign_default_account'::character varying, 'queue_for_review'::character varying, 'reject'::character varying])::text[])))
);


--
-- Name: email_system_config_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.email_system_config_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: email_system_config_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.email_system_config_id_seq OWNED BY public.email_system_config.id;


--
-- Name: email_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.email_templates (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    subject character varying(255) NOT NULL,
    body text NOT NULL,
    type character varying(255) NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT email_templates_type_check CHECK (((type)::text = ANY ((ARRAY['ticket_created'::character varying, 'ticket_updated'::character varying, 'ticket_assigned'::character varying, 'custom'::character varying])::text[])))
);


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: file_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.file_attachments (
    id uuid NOT NULL,
    entity_type character varying(255) NOT NULL,
    entity_id uuid NOT NULL,
    original_name character varying(255) NOT NULL,
    stored_name character varying(255) NOT NULL,
    mime_type character varying(255) NOT NULL,
    file_size bigint NOT NULL,
    path character varying(255) NOT NULL,
    uploaded_by uuid NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: import_jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_jobs (
    id uuid NOT NULL,
    profile_id uuid NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    mode character varying(255) DEFAULT 'sync'::character varying NOT NULL,
    mode_config json,
    total_records integer DEFAULT 0 NOT NULL,
    processed_records integer DEFAULT 0 NOT NULL,
    successful_records integer DEFAULT 0 NOT NULL,
    failed_records integer DEFAULT 0 NOT NULL,
    skipped_records integer DEFAULT 0 NOT NULL,
    updated_records integer DEFAULT 0 NOT NULL,
    errors json,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    duration integer,
    started_by uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT import_jobs_mode_check CHECK (((mode)::text = ANY ((ARRAY['sync'::character varying, 'append'::character varying, 'update'::character varying])::text[]))),
    CONSTRAINT import_jobs_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'running'::character varying, 'completed'::character varying, 'failed'::character varying, 'canceled'::character varying])::text[])))
);


--
-- Name: import_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_mappings (
    id uuid NOT NULL,
    profile_id uuid NOT NULL,
    source_field character varying(255) NOT NULL,
    destination_table character varying(255) NOT NULL,
    destination_field character varying(255) NOT NULL,
    data_type character varying(255) NOT NULL,
    transformation character varying(255),
    transformation_config json,
    is_required boolean DEFAULT false NOT NULL,
    default_value character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT import_mappings_data_type_check CHECK (((data_type)::text = ANY ((ARRAY['string'::character varying, 'integer'::character varying, 'decimal'::character varying, 'boolean'::character varying, 'date'::character varying, 'datetime'::character varying, 'json'::character varying])::text[])))
);


--
-- Name: import_profiles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_profiles (
    id uuid NOT NULL,
    template_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    source_type character varying(255) NOT NULL,
    connection_config json,
    configuration json,
    import_mode character varying(255) DEFAULT 'sync'::character varying NOT NULL,
    enable_scheduling boolean DEFAULT false NOT NULL,
    schedule_frequency character varying(255),
    schedule_time time(0) without time zone,
    schedule_days json,
    last_sync_at timestamp(0) without time zone,
    next_sync_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT import_profiles_import_mode_check CHECK (((import_mode)::text = ANY ((ARRAY['sync'::character varying, 'append'::character varying, 'update'::character varying])::text[]))),
    CONSTRAINT import_profiles_schedule_frequency_check CHECK (((schedule_frequency)::text = ANY ((ARRAY['hourly'::character varying, 'daily'::character varying, 'weekly'::character varying, 'monthly'::character varying])::text[]))),
    CONSTRAINT import_profiles_source_type_check CHECK (((source_type)::text = ANY ((ARRAY['database'::character varying, 'api'::character varying, 'file'::character varying])::text[])))
);


--
-- Name: import_queries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_queries (
    id uuid NOT NULL,
    profile_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    query text NOT NULL,
    target_table character varying(255) NOT NULL,
    parameters json,
    execution_order integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: import_records; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_records (
    id uuid NOT NULL,
    job_id uuid NOT NULL,
    external_id character varying(255),
    record_type character varying(255) NOT NULL,
    local_id uuid,
    status character varying(255) NOT NULL,
    error_message text,
    source_data json,
    transformed_data json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT import_records_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'success'::character varying, 'failed'::character varying, 'skipped'::character varying])::text[])))
);


--
-- Name: import_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.import_templates (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    source_type character varying(255) NOT NULL,
    default_configuration json,
    field_mappings json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT import_templates_source_type_check CHECK (((source_type)::text = ANY ((ARRAY['database'::character varying, 'api'::character varying, 'file'::character varying])::text[])))
);


--
-- Name: invoice_line_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_line_items (
    id uuid NOT NULL,
    invoice_id uuid NOT NULL,
    type character varying(255) NOT NULL,
    billable_id uuid,
    billable_type character varying(255),
    description character varying(255) NOT NULL,
    quantity integer DEFAULT 1 NOT NULL,
    unit_price numeric(10,2) NOT NULL,
    total numeric(10,2) NOT NULL,
    taxable boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    is_separator boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT invoice_line_items_type_check CHECK (((type)::text = ANY ((ARRAY['time_entry'::character varying, 'ticket_addon'::character varying, 'custom'::character varying])::text[])))
);


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices (
    id uuid NOT NULL,
    invoice_number character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    subtotal numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    invoice_date date NOT NULL,
    due_date date NOT NULL,
    notes text,
    billing_address json,
    tax_application_mode character varying(255) DEFAULT 'exclusive'::character varying NOT NULL,
    override_tax boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT invoices_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'pending'::character varying, 'sent'::character varying, 'paid'::character varying, 'overdue'::character varying, 'canceled'::character varying])::text[]))),
    CONSTRAINT invoices_tax_application_mode_check CHECK (((tax_application_mode)::text = ANY ((ARRAY['inclusive'::character varying, 'exclusive'::character varying])::text[])))
);


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.notifications (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    type character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    message text NOT NULL,
    data json,
    is_read boolean DEFAULT false NOT NULL,
    read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: page_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.page_permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    page_route character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: page_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.page_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: page_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.page_permissions_id_seq OWNED BY public.page_permissions.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: payments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.payments (
    id uuid NOT NULL,
    invoice_id uuid NOT NULL,
    amount numeric(10,2) NOT NULL,
    method character varying(255) NOT NULL,
    reference character varying(255),
    notes text,
    payment_date timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT payments_method_check CHECK (((method)::text = ANY ((ARRAY['cash'::character varying, 'check'::character varying, 'credit_card'::character varying, 'bank_transfer'::character varying, 'other'::character varying])::text[])))
);


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    "group" character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.permissions_id_seq OWNED BY public.permissions.id;


--
-- Name: role_template_widgets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_template_widgets (
    id bigint NOT NULL,
    role_template_id uuid NOT NULL,
    widget_permission_id bigint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: role_template_widgets_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.role_template_widgets_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: role_template_widgets_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.role_template_widgets_id_seq OWNED BY public.role_template_widgets.id;


--
-- Name: role_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_templates (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    display_name character varying(255),
    description text,
    context character varying(255) DEFAULT 'both'::character varying NOT NULL,
    account_id uuid,
    permissions json,
    widget_permissions json,
    page_permissions json,
    dashboard_layout json,
    is_system_role boolean DEFAULT false NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    is_modifiable boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT role_templates_context_check CHECK (((context)::text = ANY ((ARRAY['service_provider'::character varying, 'account_user'::character varying, 'both'::character varying])::text[])))
);


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.roles (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    role_template_id uuid NOT NULL,
    custom_permissions json,
    custom_widget_permissions json,
    custom_page_permissions json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id uuid,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.settings (
    id uuid NOT NULL,
    key character varying(255) NOT NULL,
    value json,
    type character varying(255) DEFAULT 'system'::character varying NOT NULL,
    account_id uuid,
    user_id uuid,
    description character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: system_logs; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.system_logs (
    id uuid NOT NULL,
    level character varying(255) NOT NULL,
    channel character varying(255),
    message text NOT NULL,
    context json,
    file character varying(255),
    line integer,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT system_logs_level_check CHECK (((level)::text = ANY ((ARRAY['emergency'::character varying, 'alert'::character varying, 'critical'::character varying, 'error'::character varying, 'warning'::character varying, 'notice'::character varying, 'info'::character varying, 'debug'::character varying])::text[])))
);


--
-- Name: tax_configurations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_configurations (
    id uuid NOT NULL,
    account_id uuid,
    name character varying(255) NOT NULL,
    rate numeric(5,4) NOT NULL,
    type character varying(255) DEFAULT 'percentage'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT tax_configurations_type_check CHECK (((type)::text = ANY ((ARRAY['percentage'::character varying, 'fixed'::character varying])::text[])))
);


--
-- Name: themes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.themes (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    configuration json NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_addons; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_addons (
    id uuid NOT NULL,
    ticket_id uuid NOT NULL,
    added_by_user_id uuid,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255),
    sku character varying(255),
    unit_price numeric(10,2) NOT NULL,
    quantity numeric(8,2) DEFAULT '1'::numeric NOT NULL,
    discount_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    billable boolean DEFAULT true NOT NULL,
    is_taxable boolean DEFAULT true NOT NULL,
    billing_category character varying(255),
    addon_template_id uuid,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by_user_id uuid,
    approved_at timestamp(0) without time zone,
    approval_notes text,
    metadata json,
    invoice_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ticket_addons_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: ticket_agent; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_agent (
    id bigint NOT NULL,
    ticket_id uuid NOT NULL,
    agent_id uuid NOT NULL,
    role character varying(255) DEFAULT 'collaborator'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ticket_agent_role_check CHECK (((role)::text = ANY ((ARRAY['primary'::character varying, 'collaborator'::character varying])::text[])))
);


--
-- Name: ticket_agent_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.ticket_agent_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: ticket_agent_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.ticket_agent_id_seq OWNED BY public.ticket_agent.id;


--
-- Name: ticket_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_categories (
    id uuid NOT NULL,
    key character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    color character varying(7) DEFAULT '#6B7280'::character varying NOT NULL,
    bg_color character varying(7) DEFAULT '#F3F4F6'::character varying NOT NULL,
    icon character varying(50),
    account_id uuid,
    is_system boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    requires_approval boolean DEFAULT false NOT NULL,
    default_priority_multiplier numeric(3,2) DEFAULT '1'::numeric NOT NULL,
    default_estimated_hours integer,
    sla_hours integer,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_comments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_comments (
    id uuid NOT NULL,
    external_id character varying(255),
    ticket_id uuid NOT NULL,
    user_id uuid NOT NULL,
    content text NOT NULL,
    type character varying(255) DEFAULT 'comment'::character varying NOT NULL,
    is_public boolean DEFAULT true NOT NULL,
    attachments json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ticket_comments_type_check CHECK (((type)::text = ANY ((ARRAY['comment'::character varying, 'internal_note'::character varying, 'system'::character varying])::text[])))
);


--
-- Name: ticket_priorities; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_priorities (
    id uuid NOT NULL,
    key character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    color character varying(255) DEFAULT '#6B7280'::character varying NOT NULL,
    bg_color character varying(255) DEFAULT '#F3F4F6'::character varying NOT NULL,
    icon character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    level integer DEFAULT 1 NOT NULL,
    severity_level integer DEFAULT 1 NOT NULL,
    escalation_multiplier numeric(3,2) DEFAULT '1'::numeric NOT NULL,
    escalation_hours integer,
    sort_order integer DEFAULT 0 NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_statuses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_statuses (
    id uuid NOT NULL,
    key character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    color character varying(7) DEFAULT '#6B7280'::character varying NOT NULL,
    bg_color character varying(7) DEFAULT '#F3F4F6'::character varying NOT NULL,
    icon character varying(50),
    account_id uuid,
    type character varying(255) DEFAULT 'open'::character varying NOT NULL,
    is_system boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_closed boolean DEFAULT false NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    billable boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ticket_statuses_type_check CHECK (((type)::text = ANY ((ARRAY['open'::character varying, 'in_progress'::character varying, 'resolved'::character varying, 'closed'::character varying])::text[])))
);


--
-- Name: tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tickets (
    id uuid NOT NULL,
    external_id character varying(255),
    ticket_number character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    status character varying(255) DEFAULT 'open'::character varying NOT NULL,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    account_id uuid NOT NULL,
    customer_id uuid,
    agent_id uuid,
    created_by_id uuid NOT NULL,
    category_id uuid,
    status_id uuid,
    priority_id uuid,
    customer_name character varying(255),
    customer_email character varying(255),
    estimated_hours integer,
    estimated_amount numeric(10,2),
    actual_amount numeric(10,2),
    billing_rate_id uuid,
    due_date timestamp(0) without time zone,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    resolved_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    custom_fields json,
    metadata json,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT tickets_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'normal'::character varying, 'high'::character varying, 'urgent'::character varying])::text[]))),
    CONSTRAINT tickets_status_check CHECK (((status)::text = ANY ((ARRAY['open'::character varying, 'in_progress'::character varying, 'resolved'::character varying, 'closed'::character varying])::text[])))
);


--
-- Name: time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_entries (
    id uuid NOT NULL,
    external_id character varying(255),
    user_id uuid NOT NULL,
    account_id uuid NOT NULL,
    ticket_id uuid,
    description character varying(255) NOT NULL,
    duration integer NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    ended_at timestamp(0) without time zone,
    billing_rate_id uuid,
    rate_at_time numeric(8,2),
    rate_override numeric(10,2),
    billed_amount numeric(10,2),
    billable boolean DEFAULT true NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by uuid,
    approved_at timestamp(0) without time zone,
    approval_notes text,
    invoice_id uuid,
    notes text,
    metadata json,
    timer_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT time_entries_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


--
-- Name: timers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.timers (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    account_id uuid NOT NULL,
    ticket_id uuid,
    description character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'running'::character varying NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    paused_at timestamp(0) without time zone,
    duration integer DEFAULT 0 NOT NULL,
    billing_rate_id uuid,
    rate_override numeric(10,2),
    time_entry_id uuid,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT timers_status_check CHECK (((status)::text = ANY ((ARRAY['running'::character varying, 'paused'::character varying, 'canceled'::character varying, 'committed'::character varying])::text[])))
);


--
-- Name: user_invitations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_invitations (
    id uuid NOT NULL,
    email character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    role_template_id uuid NOT NULL,
    invited_by uuid NOT NULL,
    token character varying(255) NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT user_invitations_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'accepted'::character varying, 'expired'::character varying])::text[])))
);


--
-- Name: user_preferences; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_preferences (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    key character varying(255) NOT NULL,
    value json NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255),
    email_verified_at timestamp(0) without time zone,
    password character varying(255),
    account_id uuid,
    user_type character varying(255) DEFAULT 'account_user'::character varying NOT NULL,
    role_template_id uuid,
    preferences json,
    timezone character varying(50) DEFAULT 'UTC'::character varying NOT NULL,
    locale character varying(10) DEFAULT 'en'::character varying NOT NULL,
    last_active_at timestamp(0) without time zone,
    last_login_at timestamp(0) without time zone,
    is_active boolean DEFAULT true NOT NULL,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    external_id character varying(255),
    visible boolean DEFAULT true NOT NULL,
    is_visible boolean DEFAULT true NOT NULL,
    CONSTRAINT users_user_type_check CHECK (((user_type)::text = ANY ((ARRAY['agent'::character varying, 'account_user'::character varying])::text[])))
);


--
-- Name: webhook_calls; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.webhook_calls (
    id uuid NOT NULL,
    webhook_id uuid NOT NULL,
    event_type character varying(255) NOT NULL,
    payload json NOT NULL,
    http_status_code integer,
    response_body text,
    response_time_ms integer,
    error_message text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: webhooks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.webhooks (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    url character varying(255) NOT NULL,
    events json NOT NULL,
    secret character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    headers json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: widget_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.widget_permissions (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    description character varying(255),
    widget_type character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: widget_permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.widget_permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: widget_permissions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.widget_permissions_id_seq OWNED BY public.widget_permissions.id;


--
-- Name: email_system_config id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_system_config ALTER COLUMN id SET DEFAULT nextval('public.email_system_config_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: page_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_permissions ALTER COLUMN id SET DEFAULT nextval('public.page_permissions_id_seq'::regclass);


--
-- Name: permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions ALTER COLUMN id SET DEFAULT nextval('public.permissions_id_seq'::regclass);


--
-- Name: role_template_widgets id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_template_widgets ALTER COLUMN id SET DEFAULT nextval('public.role_template_widgets_id_seq'::regclass);


--
-- Name: ticket_agent id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent ALTER COLUMN id SET DEFAULT nextval('public.ticket_agent_id_seq'::regclass);


--
-- Name: widget_permissions id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_permissions ALTER COLUMN id SET DEFAULT nextval('public.widget_permissions_id_seq'::regclass);


--
-- Name: accounts accounts_external_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_external_id_unique UNIQUE (external_id);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


--
-- Name: activity_log activity_log_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_pkey PRIMARY KEY (id);


--
-- Name: addon_templates addon_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addon_templates
    ADD CONSTRAINT addon_templates_pkey PRIMARY KEY (id);


--
-- Name: billing_rates billing_rates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_rates
    ADD CONSTRAINT billing_rates_pkey PRIMARY KEY (id);


--
-- Name: billing_schedules billing_schedules_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_schedules
    ADD CONSTRAINT billing_schedules_pkey PRIMARY KEY (id);


--
-- Name: billing_settings billing_settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_settings
    ADD CONSTRAINT billing_settings_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: categories categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_pkey PRIMARY KEY (id);


--
-- Name: custom_field_values custom_field_values_custom_field_id_entity_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_field_values
    ADD CONSTRAINT custom_field_values_custom_field_id_entity_id_unique UNIQUE (custom_field_id, entity_id);


--
-- Name: custom_field_values custom_field_values_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_field_values
    ADD CONSTRAINT custom_field_values_pkey PRIMARY KEY (id);


--
-- Name: custom_fields custom_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_fields
    ADD CONSTRAINT custom_fields_pkey PRIMARY KEY (id);


--
-- Name: domain_mappings domain_mappings_domain_account_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.domain_mappings
    ADD CONSTRAINT domain_mappings_domain_account_id_unique UNIQUE (domain, account_id);


--
-- Name: domain_mappings domain_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.domain_mappings
    ADD CONSTRAINT domain_mappings_pkey PRIMARY KEY (id);


--
-- Name: email_configs email_configs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_configs
    ADD CONSTRAINT email_configs_pkey PRIMARY KEY (id);


--
-- Name: email_domain_mappings email_domain_mappings_domain_account_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_domain_mappings
    ADD CONSTRAINT email_domain_mappings_domain_account_id_unique UNIQUE (domain, account_id);


--
-- Name: email_domain_mappings email_domain_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_domain_mappings
    ADD CONSTRAINT email_domain_mappings_pkey PRIMARY KEY (id);


--
-- Name: email_processing_logs email_processing_logs_email_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_processing_logs
    ADD CONSTRAINT email_processing_logs_email_id_unique UNIQUE (email_id);


--
-- Name: email_processing_logs email_processing_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_processing_logs
    ADD CONSTRAINT email_processing_logs_pkey PRIMARY KEY (id);


--
-- Name: email_system_config email_system_config_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_system_config
    ADD CONSTRAINT email_system_config_pkey PRIMARY KEY (id);


--
-- Name: email_templates email_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_templates
    ADD CONSTRAINT email_templates_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: file_attachments file_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.file_attachments
    ADD CONSTRAINT file_attachments_pkey PRIMARY KEY (id);


--
-- Name: import_jobs import_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_pkey PRIMARY KEY (id);


--
-- Name: import_mappings import_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_mappings
    ADD CONSTRAINT import_mappings_pkey PRIMARY KEY (id);


--
-- Name: import_profiles import_profiles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_profiles
    ADD CONSTRAINT import_profiles_pkey PRIMARY KEY (id);


--
-- Name: import_queries import_queries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_queries
    ADD CONSTRAINT import_queries_pkey PRIMARY KEY (id);


--
-- Name: import_records import_records_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_records
    ADD CONSTRAINT import_records_pkey PRIMARY KEY (id);


--
-- Name: import_templates import_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_templates
    ADD CONSTRAINT import_templates_pkey PRIMARY KEY (id);


--
-- Name: invoice_line_items invoice_line_items_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line_items
    ADD CONSTRAINT invoice_line_items_pkey PRIMARY KEY (id);


--
-- Name: invoices invoices_invoice_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_invoice_number_unique UNIQUE (invoice_number);


--
-- Name: invoices invoices_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: notifications notifications_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_pkey PRIMARY KEY (id);


--
-- Name: page_permissions page_permissions_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_permissions
    ADD CONSTRAINT page_permissions_name_unique UNIQUE (name);


--
-- Name: page_permissions page_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_permissions
    ADD CONSTRAINT page_permissions_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: payments payments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_pkey PRIMARY KEY (id);


--
-- Name: permissions permissions_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_name_unique UNIQUE (name);


--
-- Name: permissions permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.permissions
    ADD CONSTRAINT permissions_pkey PRIMARY KEY (id);


--
-- Name: role_template_widgets role_template_widgets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_template_widgets
    ADD CONSTRAINT role_template_widgets_pkey PRIMARY KEY (id);


--
-- Name: role_template_widgets role_template_widgets_role_template_id_widget_permission_id_uni; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_template_widgets
    ADD CONSTRAINT role_template_widgets_role_template_id_widget_permission_id_uni UNIQUE (role_template_id, widget_permission_id);


--
-- Name: role_templates role_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_templates
    ADD CONSTRAINT role_templates_pkey PRIMARY KEY (id);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: roles roles_user_id_role_template_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_user_id_role_template_id_unique UNIQUE (user_id, role_template_id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_key_unique UNIQUE (key);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


--
-- Name: system_logs system_logs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.system_logs
    ADD CONSTRAINT system_logs_pkey PRIMARY KEY (id);


--
-- Name: tax_configurations tax_configurations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_configurations
    ADD CONSTRAINT tax_configurations_pkey PRIMARY KEY (id);


--
-- Name: themes themes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.themes
    ADD CONSTRAINT themes_pkey PRIMARY KEY (id);


--
-- Name: ticket_addons ticket_addons_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_addons
    ADD CONSTRAINT ticket_addons_pkey PRIMARY KEY (id);


--
-- Name: ticket_agent ticket_agent_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_pkey PRIMARY KEY (id);


--
-- Name: ticket_agent ticket_agent_ticket_id_agent_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_ticket_id_agent_id_unique UNIQUE (ticket_id, agent_id);


--
-- Name: ticket_categories ticket_categories_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_categories
    ADD CONSTRAINT ticket_categories_key_unique UNIQUE (key);


--
-- Name: ticket_categories ticket_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_categories
    ADD CONSTRAINT ticket_categories_pkey PRIMARY KEY (id);


--
-- Name: ticket_comments ticket_comments_external_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_external_id_unique UNIQUE (external_id);


--
-- Name: ticket_comments ticket_comments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_pkey PRIMARY KEY (id);


--
-- Name: ticket_priorities ticket_priorities_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priorities
    ADD CONSTRAINT ticket_priorities_key_unique UNIQUE (key);


--
-- Name: ticket_priorities ticket_priorities_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_priorities
    ADD CONSTRAINT ticket_priorities_pkey PRIMARY KEY (id);


--
-- Name: ticket_statuses ticket_statuses_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_statuses
    ADD CONSTRAINT ticket_statuses_key_unique UNIQUE (key);


--
-- Name: ticket_statuses ticket_statuses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_statuses
    ADD CONSTRAINT ticket_statuses_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_external_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_external_id_unique UNIQUE (external_id);


--
-- Name: tickets tickets_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_pkey PRIMARY KEY (id);


--
-- Name: tickets tickets_ticket_number_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_ticket_number_unique UNIQUE (ticket_number);


--
-- Name: time_entries time_entries_external_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_external_id_unique UNIQUE (external_id);


--
-- Name: time_entries time_entries_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_pkey PRIMARY KEY (id);


--
-- Name: timers timers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_pkey PRIMARY KEY (id);


--
-- Name: invoice_line_items unique_billable_item; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line_items
    ADD CONSTRAINT unique_billable_item UNIQUE (billable_type, billable_id);


--
-- Name: user_invitations user_invitations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invitations
    ADD CONSTRAINT user_invitations_pkey PRIMARY KEY (id);


--
-- Name: user_invitations user_invitations_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invitations
    ADD CONSTRAINT user_invitations_token_unique UNIQUE (token);


--
-- Name: user_preferences user_preferences_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_preferences
    ADD CONSTRAINT user_preferences_pkey PRIMARY KEY (id);


--
-- Name: user_preferences user_preferences_user_id_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_preferences
    ADD CONSTRAINT user_preferences_user_id_key_unique UNIQUE (user_id, key);


--
-- Name: users users_email_user_type_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_user_type_unique UNIQUE (email, user_type);


--
-- Name: users users_external_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_external_id_unique UNIQUE (external_id);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: webhook_calls webhook_calls_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_calls
    ADD CONSTRAINT webhook_calls_pkey PRIMARY KEY (id);


--
-- Name: webhooks webhooks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhooks
    ADD CONSTRAINT webhooks_pkey PRIMARY KEY (id);


--
-- Name: widget_permissions widget_permissions_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_permissions
    ADD CONSTRAINT widget_permissions_name_unique UNIQUE (name);


--
-- Name: widget_permissions widget_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_permissions
    ADD CONSTRAINT widget_permissions_pkey PRIMARY KEY (id);


--
-- Name: accounts_account_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_account_type_is_active_index ON public.accounts USING btree (account_type, is_active);


--
-- Name: activity_log_entity_type_entity_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_log_entity_type_entity_id_index ON public.activity_log USING btree (entity_type, entity_id);


--
-- Name: activity_log_event_type_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_log_event_type_created_at_index ON public.activity_log USING btree (event_type, created_at);


--
-- Name: activity_log_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX activity_log_user_id_created_at_index ON public.activity_log USING btree (user_id, created_at);


--
-- Name: addon_templates_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addon_templates_is_active_index ON public.addon_templates USING btree (is_active);


--
-- Name: billing_rates_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_rates_account_id_is_active_index ON public.billing_rates USING btree (account_id, is_active);


--
-- Name: billing_rates_is_default_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_rates_is_default_is_active_index ON public.billing_rates USING btree (is_default, is_active);


--
-- Name: billing_rates_user_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_rates_user_id_is_active_index ON public.billing_rates USING btree (user_id, is_active);


--
-- Name: billing_schedules_is_active_next_generation_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_schedules_is_active_next_generation_at_index ON public.billing_schedules USING btree (is_active, next_generation_at);


--
-- Name: categories_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_type_is_active_index ON public.categories USING btree (type, is_active);


--
-- Name: custom_field_values_entity_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_field_values_entity_id_index ON public.custom_field_values USING btree (entity_id);


--
-- Name: custom_fields_entity_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_fields_entity_type_is_active_index ON public.custom_fields USING btree (entity_type, is_active);


--
-- Name: custom_fields_entity_type_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_fields_entity_type_sort_order_index ON public.custom_fields USING btree (entity_type, sort_order);


--
-- Name: domain_mappings_domain_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX domain_mappings_domain_is_active_index ON public.domain_mappings USING btree (domain, is_active);


--
-- Name: email_configs_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_configs_account_id_is_active_index ON public.email_configs USING btree (account_id, is_active);


--
-- Name: email_domain_mappings_domain_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_domain_mappings_domain_is_active_index ON public.email_domain_mappings USING btree (domain, is_active);


--
-- Name: email_domain_mappings_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_domain_mappings_is_active_sort_order_index ON public.email_domain_mappings USING btree (is_active, sort_order);


--
-- Name: email_processing_logs_from_address_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_processing_logs_from_address_status_index ON public.email_processing_logs USING btree (from_address, status);


--
-- Name: email_processing_logs_is_command_command_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_processing_logs_is_command_command_status_index ON public.email_processing_logs USING btree (is_command, command_status);


--
-- Name: email_processing_logs_message_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_processing_logs_message_id_index ON public.email_processing_logs USING btree (message_id);


--
-- Name: email_processing_logs_status_received_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_processing_logs_status_received_at_index ON public.email_processing_logs USING btree (status, received_at);


--
-- Name: email_system_config_default_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_system_config_default_account_id_index ON public.email_system_config USING btree (default_account_id);


--
-- Name: email_system_config_default_role_template_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_system_config_default_role_template_id_index ON public.email_system_config USING btree (default_role_template_id);


--
-- Name: email_templates_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX email_templates_type_is_active_index ON public.email_templates USING btree (type, is_active);


--
-- Name: file_attachments_entity_type_entity_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX file_attachments_entity_type_entity_id_index ON public.file_attachments USING btree (entity_type, entity_id);


--
-- Name: file_attachments_uploaded_by_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX file_attachments_uploaded_by_created_at_index ON public.file_attachments USING btree (uploaded_by, created_at);


--
-- Name: import_jobs_profile_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_profile_id_status_index ON public.import_jobs USING btree (profile_id, status);


--
-- Name: import_jobs_status_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_jobs_status_created_at_index ON public.import_jobs USING btree (status, created_at);


--
-- Name: import_mappings_profile_id_destination_table_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_mappings_profile_id_destination_table_index ON public.import_mappings USING btree (profile_id, destination_table);


--
-- Name: import_profiles_enable_scheduling_next_sync_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_profiles_enable_scheduling_next_sync_at_index ON public.import_profiles USING btree (enable_scheduling, next_sync_at);


--
-- Name: import_profiles_source_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_profiles_source_type_is_active_index ON public.import_profiles USING btree (source_type, is_active);


--
-- Name: import_queries_profile_id_execution_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_queries_profile_id_execution_order_index ON public.import_queries USING btree (profile_id, execution_order);


--
-- Name: import_records_external_id_record_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_records_external_id_record_type_index ON public.import_records USING btree (external_id, record_type);


--
-- Name: import_records_job_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_records_job_id_status_index ON public.import_records USING btree (job_id, status);


--
-- Name: import_records_local_id_record_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_records_local_id_record_type_index ON public.import_records USING btree (local_id, record_type);


--
-- Name: import_templates_source_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX import_templates_source_type_is_active_index ON public.import_templates USING btree (source_type, is_active);


--
-- Name: invoice_line_items_billable_type_billable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_line_items_billable_type_billable_id_index ON public.invoice_line_items USING btree (billable_type, billable_id);


--
-- Name: invoice_line_items_invoice_id_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_line_items_invoice_id_sort_order_index ON public.invoice_line_items USING btree (invoice_id, sort_order);


--
-- Name: invoices_account_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_account_id_status_index ON public.invoices USING btree (account_id, status);


--
-- Name: invoices_status_due_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_status_due_date_index ON public.invoices USING btree (status, due_date);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: notifications_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_user_id_created_at_index ON public.notifications USING btree (user_id, created_at);


--
-- Name: notifications_user_id_is_read_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX notifications_user_id_is_read_index ON public.notifications USING btree (user_id, is_read);


--
-- Name: page_permissions_page_route_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_permissions_page_route_index ON public.page_permissions USING btree (page_route);


--
-- Name: payments_invoice_id_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_invoice_id_payment_date_index ON public.payments USING btree (invoice_id, payment_date);


--
-- Name: permissions_group_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_group_name_index ON public.permissions USING btree ("group", name);


--
-- Name: role_templates_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_templates_account_id_index ON public.role_templates USING btree (account_id);


--
-- Name: role_templates_context_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_templates_context_is_active_index ON public.role_templates USING btree (context, is_active);


--
-- Name: role_templates_is_system_role_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_templates_is_system_role_is_active_index ON public.role_templates USING btree (is_system_role, is_active);


--
-- Name: roles_user_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX roles_user_id_is_active_index ON public.roles USING btree (user_id, is_active);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settings_account_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_account_id_type_index ON public.settings USING btree (account_id, type);


--
-- Name: settings_type_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_type_key_index ON public.settings USING btree (type, key);


--
-- Name: settings_user_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_user_id_type_index ON public.settings USING btree (user_id, type);


--
-- Name: system_logs_channel_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_logs_channel_created_at_index ON public.system_logs USING btree (channel, created_at);


--
-- Name: system_logs_level_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX system_logs_level_created_at_index ON public.system_logs USING btree (level, created_at);


--
-- Name: tax_configurations_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tax_configurations_account_id_is_active_index ON public.tax_configurations USING btree (account_id, is_active);


--
-- Name: themes_is_active_is_default_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX themes_is_active_is_default_index ON public.themes USING btree (is_active, is_default);


--
-- Name: ticket_addons_added_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_addons_added_by_user_id_index ON public.ticket_addons USING btree (added_by_user_id);


--
-- Name: ticket_addons_approved_by_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_addons_approved_by_user_id_index ON public.ticket_addons USING btree (approved_by_user_id);


--
-- Name: ticket_addons_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_addons_category_index ON public.ticket_addons USING btree (category);


--
-- Name: ticket_addons_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_addons_status_index ON public.ticket_addons USING btree (status);


--
-- Name: ticket_addons_ticket_id_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_addons_ticket_id_billable_index ON public.ticket_addons USING btree (ticket_id, billable);


--
-- Name: ticket_categories_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_categories_account_id_is_active_index ON public.ticket_categories USING btree (account_id, is_active);


--
-- Name: ticket_categories_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_categories_is_active_sort_order_index ON public.ticket_categories USING btree (is_active, sort_order);


--
-- Name: ticket_comments_ticket_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_ticket_id_type_index ON public.ticket_comments USING btree (ticket_id, type);


--
-- Name: ticket_comments_user_id_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_user_id_type_index ON public.ticket_comments USING btree (user_id, type);


--
-- Name: ticket_priorities_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priorities_is_active_sort_order_index ON public.ticket_priorities USING btree (is_active, sort_order);


--
-- Name: ticket_priorities_level_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priorities_level_is_active_index ON public.ticket_priorities USING btree (level, is_active);


--
-- Name: ticket_statuses_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_statuses_account_id_is_active_index ON public.ticket_statuses USING btree (account_id, is_active);


--
-- Name: ticket_statuses_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_statuses_is_active_sort_order_index ON public.ticket_statuses USING btree (is_active, sort_order);


--
-- Name: ticket_statuses_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_statuses_type_is_active_index ON public.ticket_statuses USING btree (type, is_active);


--
-- Name: tickets_account_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_account_id_status_index ON public.tickets USING btree (account_id, status);


--
-- Name: tickets_agent_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_agent_id_status_index ON public.tickets USING btree (agent_id, status);


--
-- Name: tickets_customer_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_customer_id_status_index ON public.tickets USING btree (customer_id, status);


--
-- Name: tickets_status_priority_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_status_priority_index ON public.tickets USING btree (status, priority);


--
-- Name: time_entries_account_id_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_account_id_billable_index ON public.time_entries USING btree (account_id, billable);


--
-- Name: time_entries_started_at_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_started_at_billable_index ON public.time_entries USING btree (started_at, billable);


--
-- Name: time_entries_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_status_index ON public.time_entries USING btree (status);


--
-- Name: time_entries_ticket_id_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_ticket_id_billable_index ON public.time_entries USING btree (ticket_id, billable);


--
-- Name: time_entries_user_id_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_user_id_billable_index ON public.time_entries USING btree (user_id, billable);


--
-- Name: timers_account_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_account_id_status_index ON public.timers USING btree (account_id, status);


--
-- Name: timers_status_started_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_status_started_at_index ON public.timers USING btree (status, started_at);


--
-- Name: timers_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_user_id_status_index ON public.timers USING btree (user_id, status);


--
-- Name: user_invitations_email_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_invitations_email_status_index ON public.user_invitations USING btree (email, status);


--
-- Name: user_invitations_token_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_invitations_token_status_index ON public.user_invitations USING btree (token, status);


--
-- Name: users_email_user_type_partial_unique; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX users_email_user_type_partial_unique ON public.users USING btree (email, user_type) WHERE (email IS NOT NULL);


--
-- Name: users_user_type_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_user_type_account_id_index ON public.users USING btree (user_type, account_id);


--
-- Name: webhook_calls_event_type_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX webhook_calls_event_type_created_at_index ON public.webhook_calls USING btree (event_type, created_at);


--
-- Name: webhook_calls_webhook_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX webhook_calls_webhook_id_created_at_index ON public.webhook_calls USING btree (webhook_id, created_at);


--
-- Name: webhooks_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX webhooks_is_active_index ON public.webhooks USING btree (is_active);


--
-- Name: widget_permissions_widget_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX widget_permissions_widget_type_index ON public.widget_permissions USING btree (widget_type);


--
-- Name: time_entries time_entry_ticket_account_check; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER time_entry_ticket_account_check BEFORE INSERT OR UPDATE ON public.time_entries FOR EACH ROW EXECUTE FUNCTION public.validate_time_entry_ticket_account();


--
-- Name: activity_log activity_log_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.activity_log
    ADD CONSTRAINT activity_log_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: billing_rates billing_rates_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_rates
    ADD CONSTRAINT billing_rates_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: billing_rates billing_rates_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_rates
    ADD CONSTRAINT billing_rates_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: billing_schedules billing_schedules_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_schedules
    ADD CONSTRAINT billing_schedules_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: custom_field_values custom_field_values_custom_field_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_field_values
    ADD CONSTRAINT custom_field_values_custom_field_id_foreign FOREIGN KEY (custom_field_id) REFERENCES public.custom_fields(id) ON DELETE CASCADE;


--
-- Name: domain_mappings domain_mappings_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.domain_mappings
    ADD CONSTRAINT domain_mappings_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: email_configs email_configs_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_configs
    ADD CONSTRAINT email_configs_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: email_domain_mappings email_domain_mappings_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_domain_mappings
    ADD CONSTRAINT email_domain_mappings_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: email_processing_logs email_processing_logs_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_processing_logs
    ADD CONSTRAINT email_processing_logs_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: email_processing_logs email_processing_logs_email_config_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_processing_logs
    ADD CONSTRAINT email_processing_logs_email_config_id_foreign FOREIGN KEY (email_config_id) REFERENCES public.email_configs(id) ON DELETE SET NULL;


--
-- Name: email_processing_logs email_processing_logs_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_processing_logs
    ADD CONSTRAINT email_processing_logs_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: email_processing_logs email_processing_logs_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_processing_logs
    ADD CONSTRAINT email_processing_logs_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: email_system_config email_system_config_updated_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.email_system_config
    ADD CONSTRAINT email_system_config_updated_by_id_foreign FOREIGN KEY (updated_by_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: file_attachments file_attachments_uploaded_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.file_attachments
    ADD CONSTRAINT file_attachments_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: import_jobs import_jobs_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_profile_id_foreign FOREIGN KEY (profile_id) REFERENCES public.import_profiles(id) ON DELETE CASCADE;


--
-- Name: import_jobs import_jobs_started_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_jobs
    ADD CONSTRAINT import_jobs_started_by_foreign FOREIGN KEY (started_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: import_mappings import_mappings_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_mappings
    ADD CONSTRAINT import_mappings_profile_id_foreign FOREIGN KEY (profile_id) REFERENCES public.import_profiles(id) ON DELETE CASCADE;


--
-- Name: import_profiles import_profiles_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_profiles
    ADD CONSTRAINT import_profiles_template_id_foreign FOREIGN KEY (template_id) REFERENCES public.import_templates(id) ON DELETE CASCADE;


--
-- Name: import_queries import_queries_profile_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_queries
    ADD CONSTRAINT import_queries_profile_id_foreign FOREIGN KEY (profile_id) REFERENCES public.import_profiles(id) ON DELETE CASCADE;


--
-- Name: import_records import_records_job_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.import_records
    ADD CONSTRAINT import_records_job_id_foreign FOREIGN KEY (job_id) REFERENCES public.import_jobs(id) ON DELETE CASCADE;


--
-- Name: invoice_line_items invoice_line_items_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line_items
    ADD CONSTRAINT invoice_line_items_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: notifications notifications_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.notifications
    ADD CONSTRAINT notifications_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: payments payments_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: role_template_widgets role_template_widgets_role_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_template_widgets
    ADD CONSTRAINT role_template_widgets_role_template_id_foreign FOREIGN KEY (role_template_id) REFERENCES public.role_templates(id) ON DELETE CASCADE;


--
-- Name: role_template_widgets role_template_widgets_widget_permission_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_template_widgets
    ADD CONSTRAINT role_template_widgets_widget_permission_id_foreign FOREIGN KEY (widget_permission_id) REFERENCES public.widget_permissions(id) ON DELETE CASCADE;


--
-- Name: role_templates role_templates_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_templates
    ADD CONSTRAINT role_templates_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: roles roles_role_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_role_template_id_foreign FOREIGN KEY (role_template_id) REFERENCES public.role_templates(id) ON DELETE CASCADE;


--
-- Name: roles roles_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: settings settings_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: settings settings_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tax_configurations tax_configurations_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tax_configurations
    ADD CONSTRAINT tax_configurations_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: ticket_addons ticket_addons_added_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_addons
    ADD CONSTRAINT ticket_addons_added_by_user_id_foreign FOREIGN KEY (added_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ticket_addons ticket_addons_addon_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_addons
    ADD CONSTRAINT ticket_addons_addon_template_id_foreign FOREIGN KEY (addon_template_id) REFERENCES public.addon_templates(id) ON DELETE SET NULL;


--
-- Name: ticket_addons ticket_addons_approved_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_addons
    ADD CONSTRAINT ticket_addons_approved_by_user_id_foreign FOREIGN KEY (approved_by_user_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: ticket_addons ticket_addons_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_addons
    ADD CONSTRAINT ticket_addons_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE SET NULL;


--
-- Name: ticket_addons ticket_addons_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_addons
    ADD CONSTRAINT ticket_addons_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_agent ticket_agent_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_agent ticket_agent_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_categories ticket_categories_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_categories
    ADD CONSTRAINT ticket_categories_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: ticket_comments ticket_comments_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_comments ticket_comments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: ticket_statuses ticket_statuses_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_statuses
    ADD CONSTRAINT ticket_statuses_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_agent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_agent_id_foreign FOREIGN KEY (agent_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_billing_rate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_billing_rate_id_foreign FOREIGN KEY (billing_rate_id) REFERENCES public.billing_rates(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.ticket_categories(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_created_by_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_created_by_id_foreign FOREIGN KEY (created_by_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: tickets tickets_customer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_customer_id_foreign FOREIGN KEY (customer_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_priority_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_priority_id_foreign FOREIGN KEY (priority_id) REFERENCES public.ticket_priorities(id) ON DELETE SET NULL;


--
-- Name: tickets tickets_status_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.tickets
    ADD CONSTRAINT tickets_status_id_foreign FOREIGN KEY (status_id) REFERENCES public.ticket_statuses(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: time_entries time_entries_approved_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_billing_rate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_billing_rate_id_foreign FOREIGN KEY (billing_rate_id) REFERENCES public.billing_rates(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_timer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_timer_id_foreign FOREIGN KEY (timer_id) REFERENCES public.timers(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: timers timers_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: timers timers_billing_rate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_billing_rate_id_foreign FOREIGN KEY (billing_rate_id) REFERENCES public.billing_rates(id) ON DELETE SET NULL;


--
-- Name: timers timers_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: timers timers_time_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_time_entry_id_foreign FOREIGN KEY (time_entry_id) REFERENCES public.time_entries(id) ON DELETE SET NULL;


--
-- Name: timers timers_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_invitations user_invitations_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invitations
    ADD CONSTRAINT user_invitations_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: user_invitations user_invitations_invited_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invitations
    ADD CONSTRAINT user_invitations_invited_by_foreign FOREIGN KEY (invited_by) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_preferences user_preferences_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_preferences
    ADD CONSTRAINT user_preferences_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: users users_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: users users_role_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_role_template_id_foreign FOREIGN KEY (role_template_id) REFERENCES public.role_templates(id) ON DELETE SET NULL;


--
-- Name: webhook_calls webhook_calls_webhook_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.webhook_calls
    ADD CONSTRAINT webhook_calls_webhook_id_foreign FOREIGN KEY (webhook_id) REFERENCES public.webhooks(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

--
-- PostgreSQL database dump
--

-- Dumped from database version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)
-- Dumped by pg_dump version 16.9 (Ubuntu 16.9-0ubuntu0.24.04.1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	0001_01_01_000003_create_core_user_and_account_management	1
5	0001_01_01_000004_create_permission_and_role_management	1
6	0001_01_01_000005_create_ticket_and_service_management	1
7	0001_01_01_000006_create_timer_and_time_entry_system	1
8	0001_01_01_000007_create_billing_and_invoice_system	1
9	0001_01_01_000008_create_universal_import_system	1
10	0001_01_01_000009_create_email_management_system	1
11	0001_01_01_000010_create_system_configuration_and_utilities	1
13	2025_08_27_222817_add_is_visible_to_users_table	2
14	2025_08_27_230905_update_email_domain_mappings_use_sort_order_instead_of_priority	3
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 14, true);


--
-- PostgreSQL database dump complete
--

