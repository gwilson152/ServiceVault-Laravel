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


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.accounts (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    company_name character varying(255),
    account_type character varying(255) DEFAULT 'customer'::character varying NOT NULL,
    description text,
    parent_id uuid,
    parent_account_id uuid,
    root_account_id uuid,
    hierarchy_level integer DEFAULT 0 NOT NULL,
    hierarchy_path character varying(255),
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
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT accounts_account_type_check CHECK (((account_type)::text = ANY ((ARRAY['customer'::character varying, 'prospect'::character varying, 'partner'::character varying, 'internal'::character varying])::text[])))
);


--
-- Name: addon_templates; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.addon_templates (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255),
    unit_type character varying(255) DEFAULT 'each'::character varying NOT NULL,
    default_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    default_quantity numeric(8,2) DEFAULT '1'::numeric NOT NULL,
    is_taxable boolean DEFAULT false NOT NULL,
    requires_approval boolean DEFAULT false NOT NULL,
    account_id uuid,
    is_system boolean DEFAULT true NOT NULL,
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
    rate numeric(8,2) NOT NULL,
    description text,
    account_id uuid,
    user_id uuid,
    is_active boolean DEFAULT true NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
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
    created_by_user_id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    frequency character varying(255) NOT NULL,
    "interval" integer DEFAULT 1 NOT NULL,
    start_date date NOT NULL,
    end_date date,
    next_billing_date date NOT NULL,
    last_billed_date date,
    is_active boolean DEFAULT true NOT NULL,
    auto_send boolean DEFAULT false NOT NULL,
    payment_terms integer DEFAULT 30 NOT NULL,
    billing_items json,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT billing_schedules_frequency_check CHECK (((frequency)::text = ANY ((ARRAY['weekly'::character varying, 'monthly'::character varying, 'quarterly'::character varying, 'annually'::character varying])::text[])))
);


--
-- Name: billing_settings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.billing_settings (
    id uuid NOT NULL,
    account_id uuid,
    company_name character varying(255) NOT NULL,
    company_address text NOT NULL,
    company_phone character varying(255),
    company_email character varying(255) NOT NULL,
    company_website character varying(255),
    tax_id character varying(255),
    invoice_prefix character varying(255) DEFAULT 'INV'::character varying NOT NULL,
    next_invoice_number integer DEFAULT 1001 NOT NULL,
    payment_terms integer DEFAULT 30 NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    timezone character varying(255) DEFAULT 'UTC'::character varying NOT NULL,
    date_format character varying(255) DEFAULT 'Y-m-d'::character varying NOT NULL,
    payment_methods json,
    auto_send_invoices boolean DEFAULT false NOT NULL,
    send_payment_reminders boolean DEFAULT true NOT NULL,
    reminder_schedule json,
    invoice_footer text,
    payment_instructions text,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    description text,
    account_id uuid NOT NULL,
    color character varying(255),
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: custom_fields; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.custom_fields (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    field_type character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    options json,
    is_required boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: domain_mappings; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.domain_mappings (
    id uuid NOT NULL,
    domain_pattern character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    role_template_id uuid,
    priority integer DEFAULT 0 NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
-- Name: invoice_line_items; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoice_line_items (
    id uuid NOT NULL,
    invoice_id uuid NOT NULL,
    time_entry_id uuid,
    ticket_addon_id uuid,
    line_type character varying(255) NOT NULL,
    description character varying(255) NOT NULL,
    quantity numeric(10,2) NOT NULL,
    unit_price numeric(10,2) NOT NULL,
    discount_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    tax_rate numeric(5,4) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(10,2) NOT NULL,
    billable boolean DEFAULT true NOT NULL,
    metadata json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: invoices; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.invoices (
    id uuid NOT NULL,
    invoice_number character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    user_id uuid NOT NULL,
    invoice_date date NOT NULL,
    due_date date NOT NULL,
    status character varying(255) DEFAULT 'draft'::character varying NOT NULL,
    subtotal numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    tax_rate numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    tax_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    total numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT invoices_status_check CHECK (((status)::text = ANY ((ARRAY['draft'::character varying, 'sent'::character varying, 'paid'::character varying, 'overdue'::character varying, 'cancelled'::character varying])::text[])))
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
-- Name: page_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.page_permissions (
    id uuid NOT NULL,
    page_key character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255),
    required_permissions json,
    is_system boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


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
    account_id uuid NOT NULL,
    payment_reference character varying(255),
    payment_method character varying(255) NOT NULL,
    payment_gateway_id character varying(255),
    transaction_id character varying(255),
    amount numeric(10,2) NOT NULL,
    fees numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    net_amount numeric(10,2) NOT NULL,
    currency character varying(3) DEFAULT 'USD'::character varying NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    payment_date timestamp(0) without time zone NOT NULL,
    processed_at timestamp(0) without time zone,
    notes text,
    gateway_response json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT payments_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'completed'::character varying, 'failed'::character varying, 'refunded'::character varying, 'cancelled'::character varying])::text[])))
);


--
-- Name: permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.permissions (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    display_name character varying(255) NOT NULL,
    description text,
    category character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: role_template_widgets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.role_template_widgets (
    id uuid NOT NULL,
    role_template_id uuid NOT NULL,
    widget_permission_id uuid NOT NULL,
    widget_config json,
    enabled_by_default boolean DEFAULT true NOT NULL,
    is_configurable boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


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
    account_id uuid NOT NULL,
    role_template_id uuid NOT NULL,
    name character varying(255),
    custom_permissions json,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: ticket_agent; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.ticket_agent (
    id bigint NOT NULL,
    ticket_id uuid NOT NULL,
    user_id uuid NOT NULL,
    role character varying(255) DEFAULT 'assignee'::character varying NOT NULL,
    assigned_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL,
    unassigned_at timestamp(0) without time zone,
    assignment_notes text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    value text,
    type character varying(255) DEFAULT 'user'::character varying NOT NULL,
    account_id uuid,
    user_id uuid,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: tax_configurations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tax_configurations (
    id uuid NOT NULL,
    account_id uuid,
    name character varying(255) NOT NULL,
    jurisdiction character varying(255) NOT NULL,
    tax_rate numeric(5,4) NOT NULL,
    tax_type character varying(255) NOT NULL,
    tax_number character varying(255),
    is_compound boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    applicable_categories json,
    effective_date date NOT NULL,
    expiry_date date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: themes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.themes (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    account_id uuid,
    settings json NOT NULL,
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
    name character varying(255) NOT NULL,
    description text,
    quantity numeric(8,2) DEFAULT '1'::numeric NOT NULL,
    unit_price numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    discount_percent numeric(5,2) DEFAULT '0'::numeric NOT NULL,
    total_amount numeric(10,2) DEFAULT '0'::numeric NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    notes text,
    billable boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT ticket_addons_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'approved'::character varying, 'rejected'::character varying])::text[])))
);


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
    ticket_id uuid NOT NULL,
    user_id uuid NOT NULL,
    content text NOT NULL,
    is_internal boolean DEFAULT false NOT NULL,
    attachments json,
    parent_id uuid,
    edited_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
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
    severity_level integer DEFAULT 1 NOT NULL,
    escalation_multiplier numeric(3,2) DEFAULT '1'::numeric NOT NULL,
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
    is_system boolean DEFAULT false NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    is_closed boolean DEFAULT false NOT NULL,
    is_default boolean DEFAULT false NOT NULL,
    billable boolean DEFAULT true NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: tickets; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tickets (
    id uuid NOT NULL,
    ticket_number character varying(255) NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    priority character varying(255) DEFAULT 'normal'::character varying NOT NULL,
    status character varying(50) DEFAULT 'open'::character varying NOT NULL,
    category character varying(50),
    account_id uuid NOT NULL,
    agent_id uuid,
    customer_id uuid,
    customer_name character varying(255),
    customer_email character varying(255),
    created_by_id uuid NOT NULL,
    estimated_hours integer,
    estimated_amount numeric(10,2),
    actual_amount numeric(10,2),
    billing_rate_id uuid,
    due_date timestamp(0) without time zone,
    started_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    resolved_at timestamp(0) without time zone,
    closed_at timestamp(0) without time zone,
    metadata json,
    settings json,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone,
    CONSTRAINT tickets_priority_check CHECK (((priority)::text = ANY ((ARRAY['low'::character varying, 'normal'::character varying, 'high'::character varying, 'urgent'::character varying])::text[])))
);


--
-- Name: time_entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.time_entries (
    id uuid NOT NULL,
    user_id uuid NOT NULL,
    account_id uuid NOT NULL,
    ticket_id uuid,
    billing_rate_id uuid,
    description text,
    started_at timestamp(0) without time zone NOT NULL,
    ended_at timestamp(0) without time zone,
    duration integer NOT NULL,
    rate_at_time numeric(8,2),
    billed_amount numeric(10,2),
    billable boolean DEFAULT true NOT NULL,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    approved_by uuid,
    approved_at timestamp(0) without time zone,
    approval_notes text,
    notes text,
    metadata json,
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
    account_id uuid,
    ticket_number character varying(255),
    description text,
    status character varying(255) DEFAULT 'running'::character varying NOT NULL,
    started_at timestamp(0) without time zone NOT NULL,
    paused_at timestamp(0) without time zone,
    stopped_at timestamp(0) without time zone,
    duration integer DEFAULT 0 NOT NULL,
    billable boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    ticket_id uuid,
    device_id character varying(255),
    is_synced boolean DEFAULT true NOT NULL,
    metadata json,
    total_paused_duration integer DEFAULT 0 NOT NULL,
    billing_rate_id uuid,
    task_id uuid,
    time_entry_id uuid,
    CONSTRAINT timers_status_check CHECK (((status)::text = ANY ((ARRAY['running'::character varying, 'paused'::character varying, 'stopped'::character varying])::text[])))
);


--
-- Name: user_invitations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_invitations (
    id uuid NOT NULL,
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    account_id uuid NOT NULL,
    invited_by uuid NOT NULL,
    role_template_id uuid,
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    accepted_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT user_invitations_status_check CHECK (((status)::text = ANY ((ARRAY['pending'::character varying, 'accepted'::character varying, 'expired'::character varying])::text[])))
);


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.users (
    id uuid NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255),
    account_id uuid,
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
    is_visible boolean DEFAULT true NOT NULL,
    user_type character varying(255) DEFAULT 'account_user'::character varying NOT NULL,
    CONSTRAINT users_user_type_check CHECK (((user_type)::text = ANY ((ARRAY['agent'::character varying, 'account_user'::character varying])::text[])))
);


--
-- Name: widget_permissions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.widget_permissions (
    id uuid NOT NULL,
    widget_key character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    description text,
    category character varying(255),
    required_permissions json,
    is_system boolean DEFAULT true NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


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
-- Name: ticket_agent id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent ALTER COLUMN id SET DEFAULT nextval('public.ticket_agent_id_seq'::regclass);


--
-- Name: accounts accounts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_pkey PRIMARY KEY (id);


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
-- Name: custom_fields custom_fields_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_fields
    ADD CONSTRAINT custom_fields_pkey PRIMARY KEY (id);


--
-- Name: domain_mappings domain_mappings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.domain_mappings
    ADD CONSTRAINT domain_mappings_pkey PRIMARY KEY (id);


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
-- Name: page_permissions page_permissions_page_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.page_permissions
    ADD CONSTRAINT page_permissions_page_key_unique UNIQUE (page_key);


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
-- Name: role_templates role_templates_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_templates
    ADD CONSTRAINT role_templates_pkey PRIMARY KEY (id);


--
-- Name: role_template_widgets role_widget_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.role_template_widgets
    ADD CONSTRAINT role_widget_unique UNIQUE (role_template_id, widget_permission_id);


--
-- Name: roles roles_account_id_role_template_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_account_id_role_template_id_unique UNIQUE (account_id, role_template_id);


--
-- Name: roles roles_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_pkey PRIMARY KEY (id);


--
-- Name: ticket_agent ticket_agent_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_pkey PRIMARY KEY (id);


--
-- Name: ticket_agent ticket_agent_ticket_id_user_id_role_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_ticket_id_user_id_role_unique UNIQUE (ticket_id, user_id, role);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: settings settings_key_account_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_key_account_id_user_id_unique UNIQUE (key, account_id, user_id);


--
-- Name: settings settings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.settings
    ADD CONSTRAINT settings_pkey PRIMARY KEY (id);


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
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: widget_permissions widget_permissions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_permissions
    ADD CONSTRAINT widget_permissions_pkey PRIMARY KEY (id);


--
-- Name: widget_permissions widget_permissions_widget_key_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.widget_permissions
    ADD CONSTRAINT widget_permissions_widget_key_unique UNIQUE (widget_key);


--
-- Name: accounts_account_type_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_account_type_is_active_index ON public.accounts USING btree (account_type, is_active);


--
-- Name: accounts_parent_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX accounts_parent_id_is_active_index ON public.accounts USING btree (parent_id, is_active);


--
-- Name: addon_templates_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addon_templates_account_id_index ON public.addon_templates USING btree (account_id);


--
-- Name: addon_templates_category_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX addon_templates_category_is_active_index ON public.addon_templates USING btree (category, is_active);


--
-- Name: billing_rates_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_rates_account_id_is_active_index ON public.billing_rates USING btree (account_id, is_active);


--
-- Name: billing_rates_user_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_rates_user_id_is_active_index ON public.billing_rates USING btree (user_id, is_active);


--
-- Name: billing_schedules_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_schedules_account_id_is_active_index ON public.billing_schedules USING btree (account_id, is_active);


--
-- Name: billing_schedules_frequency_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_schedules_frequency_is_active_index ON public.billing_schedules USING btree (frequency, is_active);


--
-- Name: billing_schedules_next_billing_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_schedules_next_billing_date_index ON public.billing_schedules USING btree (next_billing_date);


--
-- Name: billing_settings_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX billing_settings_account_id_index ON public.billing_settings USING btree (account_id);


--
-- Name: categories_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX categories_account_id_is_active_index ON public.categories USING btree (account_id, is_active);


--
-- Name: custom_fields_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX custom_fields_account_id_index ON public.custom_fields USING btree (account_id);


--
-- Name: domain_mappings_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX domain_mappings_account_id_index ON public.domain_mappings USING btree (account_id);


--
-- Name: domain_mappings_domain_pattern_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX domain_mappings_domain_pattern_is_active_index ON public.domain_mappings USING btree (domain_pattern, is_active);


--
-- Name: invoice_line_items_invoice_id_line_type_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_line_items_invoice_id_line_type_index ON public.invoice_line_items USING btree (invoice_id, line_type);


--
-- Name: invoice_line_items_ticket_addon_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_line_items_ticket_addon_id_index ON public.invoice_line_items USING btree (ticket_addon_id);


--
-- Name: invoice_line_items_time_entry_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoice_line_items_time_entry_id_index ON public.invoice_line_items USING btree (time_entry_id);


--
-- Name: invoices_account_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_account_id_status_index ON public.invoices USING btree (account_id, status);


--
-- Name: invoices_invoice_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX invoices_invoice_number_index ON public.invoices USING btree (invoice_number);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: page_permissions_category_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX page_permissions_category_is_active_index ON public.page_permissions USING btree (category, is_active);


--
-- Name: payments_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_account_id_index ON public.payments USING btree (account_id);


--
-- Name: payments_invoice_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_invoice_id_status_index ON public.payments USING btree (invoice_id, status);


--
-- Name: payments_payment_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_payment_date_index ON public.payments USING btree (payment_date);


--
-- Name: payments_transaction_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX payments_transaction_id_index ON public.payments USING btree (transaction_id);


--
-- Name: permissions_category_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX permissions_category_index ON public.permissions USING btree (category);


--
-- Name: role_templates_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_templates_account_id_index ON public.role_templates USING btree (account_id);


--
-- Name: role_templates_context_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX role_templates_context_is_active_index ON public.role_templates USING btree (context, is_active);


--
-- Name: roles_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX roles_account_id_is_active_index ON public.roles USING btree (account_id, is_active);


--
-- Name: ticket_agent_ticket_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_agent_ticket_id_index ON public.ticket_agent USING btree (ticket_id);


--
-- Name: ticket_agent_ticket_id_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_agent_ticket_id_user_id_index ON public.ticket_agent USING btree (ticket_id, user_id);


--
-- Name: ticket_agent_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_agent_user_id_index ON public.ticket_agent USING btree (user_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: settings_type_key_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX settings_type_key_index ON public.settings USING btree (type, key);


--
-- Name: tax_configurations_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tax_configurations_account_id_is_active_index ON public.tax_configurations USING btree (account_id, is_active);


--
-- Name: tax_configurations_effective_date_expiry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tax_configurations_effective_date_expiry_date_index ON public.tax_configurations USING btree (effective_date, expiry_date);


--
-- Name: tax_configurations_jurisdiction_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tax_configurations_jurisdiction_index ON public.tax_configurations USING btree (jurisdiction);


--
-- Name: themes_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX themes_account_id_index ON public.themes USING btree (account_id);


--
-- Name: ticket_addons_ticket_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_addons_ticket_id_status_index ON public.ticket_addons USING btree (ticket_id, status);


--
-- Name: ticket_categories_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_categories_account_id_is_active_index ON public.ticket_categories USING btree (account_id, is_active);


--
-- Name: ticket_comments_parent_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_parent_id_index ON public.ticket_comments USING btree (parent_id);


--
-- Name: ticket_comments_ticket_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_ticket_id_created_at_index ON public.ticket_comments USING btree (ticket_id, created_at);


--
-- Name: ticket_comments_user_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_comments_user_id_created_at_index ON public.ticket_comments USING btree (user_id, created_at);


--
-- Name: ticket_priorities_is_active_sort_order_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_priorities_is_active_sort_order_index ON public.ticket_priorities USING btree (is_active, sort_order);


--
-- Name: ticket_statuses_account_id_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX ticket_statuses_account_id_is_active_index ON public.ticket_statuses USING btree (account_id, is_active);


--
-- Name: tickets_account_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_account_id_status_index ON public.tickets USING btree (account_id, status);


--
-- Name: tickets_ticket_number_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX tickets_ticket_number_index ON public.tickets USING btree (ticket_number);


--
-- Name: time_entries_account_id_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_account_id_billable_index ON public.time_entries USING btree (account_id, billable);


--
-- Name: time_entries_ticket_id_billable_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_ticket_id_billable_index ON public.time_entries USING btree (ticket_id, billable);


--
-- Name: time_entries_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX time_entries_user_id_status_index ON public.time_entries USING btree (user_id, status);


--
-- Name: timers_account_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_account_id_status_index ON public.timers USING btree (account_id, status);


--
-- Name: timers_billing_rate_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_billing_rate_id_index ON public.timers USING btree (billing_rate_id);


--
-- Name: timers_device_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_device_id_index ON public.timers USING btree (device_id);


--
-- Name: timers_is_synced_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_is_synced_index ON public.timers USING btree (is_synced);


--
-- Name: timers_user_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_user_id_status_index ON public.timers USING btree (user_id, status);


--
-- Name: timers_user_id_ticket_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX timers_user_id_ticket_id_status_index ON public.timers USING btree (user_id, ticket_id, status);


--
-- Name: user_invitations_email_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_invitations_email_status_index ON public.user_invitations USING btree (email, status);


--
-- Name: user_invitations_token_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_invitations_token_index ON public.user_invitations USING btree (token);


--
-- Name: users_user_type_account_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX users_user_type_account_id_index ON public.users USING btree (user_type, account_id);


--
-- Name: widget_permissions_category_is_active_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX widget_permissions_category_is_active_index ON public.widget_permissions USING btree (category, is_active);


--
-- Name: time_entries time_entry_ticket_account_consistency_trigger; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER time_entry_ticket_account_consistency_trigger BEFORE INSERT OR UPDATE ON public.time_entries FOR EACH ROW EXECUTE FUNCTION public.check_time_entry_ticket_account_consistency();


--
-- Name: accounts accounts_parent_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_parent_account_id_foreign FOREIGN KEY (parent_account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: accounts accounts_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: accounts accounts_root_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.accounts
    ADD CONSTRAINT accounts_root_account_id_foreign FOREIGN KEY (root_account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: addon_templates addon_templates_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.addon_templates
    ADD CONSTRAINT addon_templates_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


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
-- Name: billing_schedules billing_schedules_created_by_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_schedules
    ADD CONSTRAINT billing_schedules_created_by_user_id_foreign FOREIGN KEY (created_by_user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: billing_settings billing_settings_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.billing_settings
    ADD CONSTRAINT billing_settings_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: categories categories_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.categories
    ADD CONSTRAINT categories_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: custom_fields custom_fields_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.custom_fields
    ADD CONSTRAINT custom_fields_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: domain_mappings domain_mappings_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.domain_mappings
    ADD CONSTRAINT domain_mappings_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: domain_mappings domain_mappings_role_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.domain_mappings
    ADD CONSTRAINT domain_mappings_role_template_id_foreign FOREIGN KEY (role_template_id) REFERENCES public.role_templates(id) ON DELETE SET NULL;


--
-- Name: invoice_line_items invoice_line_items_invoice_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line_items
    ADD CONSTRAINT invoice_line_items_invoice_id_foreign FOREIGN KEY (invoice_id) REFERENCES public.invoices(id) ON DELETE CASCADE;


--
-- Name: invoice_line_items invoice_line_items_ticket_addon_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line_items
    ADD CONSTRAINT invoice_line_items_ticket_addon_id_foreign FOREIGN KEY (ticket_addon_id) REFERENCES public.ticket_addons(id) ON DELETE SET NULL;


--
-- Name: invoice_line_items invoice_line_items_time_entry_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoice_line_items
    ADD CONSTRAINT invoice_line_items_time_entry_id_foreign FOREIGN KEY (time_entry_id) REFERENCES public.time_entries(id) ON DELETE SET NULL;


--
-- Name: invoices invoices_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: invoices invoices_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.invoices
    ADD CONSTRAINT invoices_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: payments payments_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.payments
    ADD CONSTRAINT payments_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


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
-- Name: roles roles_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: roles roles_role_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.roles
    ADD CONSTRAINT roles_role_template_id_foreign FOREIGN KEY (role_template_id) REFERENCES public.role_templates(id) ON DELETE CASCADE;


--
-- Name: ticket_agent ticket_agent_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_agent ticket_agent_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_agent
    ADD CONSTRAINT ticket_agent_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


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
-- Name: themes themes_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.themes
    ADD CONSTRAINT themes_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: ticket_addons ticket_addons_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_addons
    ADD CONSTRAINT ticket_addons_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


--
-- Name: ticket_categories ticket_categories_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_categories
    ADD CONSTRAINT ticket_categories_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE CASCADE;


--
-- Name: ticket_comments ticket_comments_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.ticket_comments
    ADD CONSTRAINT ticket_comments_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.ticket_comments(id) ON DELETE CASCADE;


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
-- Name: time_entries time_entries_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


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
-- Name: time_entries time_entries_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE SET NULL;


--
-- Name: time_entries time_entries_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.time_entries
    ADD CONSTRAINT time_entries_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: timers timers_account_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_account_id_foreign FOREIGN KEY (account_id) REFERENCES public.accounts(id) ON DELETE SET NULL;


--
-- Name: timers timers_billing_rate_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_billing_rate_id_foreign FOREIGN KEY (billing_rate_id) REFERENCES public.billing_rates(id) ON DELETE SET NULL;


--
-- Name: timers timers_ticket_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.timers
    ADD CONSTRAINT timers_ticket_id_foreign FOREIGN KEY (ticket_id) REFERENCES public.tickets(id) ON DELETE CASCADE;


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
-- Name: user_invitations user_invitations_role_template_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_invitations
    ADD CONSTRAINT user_invitations_role_template_id_foreign FOREIGN KEY (role_template_id) REFERENCES public.role_templates(id) ON DELETE SET NULL;


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
4	2025_08_11_200001_create_accounts_table	1
5	2025_08_11_200002_create_role_templates_table	1
6	2025_08_11_200003_create_permissions_table	1
7	2025_08_11_200004_create_widget_permissions_table	1
8	2025_08_11_200005_create_page_permissions_table	1
9	2025_08_11_200007_create_settings_table	1
10	2025_08_11_200008_create_categories_table	1
11	2025_08_11_200009_create_billing_rates_table	1
12	2025_08_11_200010_create_timers_table	1
13	2025_08_11_200012_create_ticket_categories_table	1
14	2025_08_11_200013_create_ticket_statuses_table	1
15	2025_08_11_200014_create_tickets_table	1
16	2025_08_11_200015_create_ticket_addons_table	1
17	2025_08_11_200016_create_roles_table	1
18	2025_08_11_200017_create_user_invitations_table	1
19	2025_08_11_200018_create_domain_mappings_table	1
20	2025_08_11_200021_create_role_template_widgets_table	1
21	2025_08_11_200023_create_themes_table	1
22	2025_08_11_200024_create_custom_fields_table	1
23	2025_08_11_200025_create_addon_templates_table	1
24	2025_08_11_200026_create_invoices_table	1
25	2025_08_11_200027_create_time_entries_table	1
26	2025_08_11_205335_create_ticket_comments_table	1
27	2025_08_11_215741_add_ticket_id_to_timers_table	1
28	2025_08_11_223528_add_missing_columns_to_timers_table	1
29	2025_08_12_192905_create_ticket_priorities_table	1
30	2025_08_12_194627_create_invoice_line_items_table	1
31	2025_08_12_194628_create_payments_table	1
32	2025_08_12_194629_create_billing_schedules_table	1
33	2025_08_12_194629_create_billing_settings_table	1
34	2025_08_12_194629_create_tax_configurations_table	1
35	2025_08_12_234839_add_visible_to_users_table	1
36	2025_08_12_235426_make_password_nullable_in_users_table	1
37	2025_08_13_141117_make_account_id_required_in_time_entries_table	1
38	2025_08_13_141221_add_user_type_to_users_table	1
39	2025_08_13_212959_create_ticket_agent_table	1
\.


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 39, true);


--
-- PostgreSQL database dump complete
--

