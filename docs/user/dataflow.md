On order module/page, I want to revamp.

For detail page, instead of page make it as modal popup.
In pending the button only show set start count / cancel order.
After setting start count:
- order status to inprogress
- start count filled
- the order is marked its processed by the user starting it
the table by default only show pending status

While in status of inprogress:
- It can set remains (no need sync)
- It can set status as completed, set remains to 0
- It can set status to cancel, with optional input cancels reason
- It can set status as partial, with input of set remains

And I want to create command to sync BJS orders for each minutes that order that not inprogress
- I think its better to add status_bjs, for tracking whether the status on the BJS already updated
- Sync process each minutes
- Has handler if the process is failed during process, for consistency data
