# On /tickets page:

-   "My Tickets" count should include tickets where user is the agent and where user is the customer.
-   Quick stats should show all active ticket statuses and the counts. The code should be efficient and implement or use a /stats/tickets endpoint. This kind of endpoing should eb commonplace.
-   the tickets list has duplicates of the assigned agent and customer name.
-   The category shows as blank/uncategorized. It is apparent that there's a category field on the tickets table instead of using a relation to ticket_categories.
