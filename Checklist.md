# Test Cases Checklist

## Type A Order Processor Tests
- [x] Successfully export order to CSV
- [x] Set high priority for orders over 200
- [x] Set low priority for orders under or equal to 200
- [x] Handle directory creation when it does not exist
- [x] Throw exception when directory creation fails
- [x] Throw exception when directory is not writable
- [x] Handle file open failure
- [x] Handle CSV headers write failure
- [x] Handle CSV data write failure
- [x] Handle flush failure 
- [x] Include order notes in CSV export
- [x] Validate order data
- [x] Handle file operation failure
- [x] Process orders with high value note
- [x] Handle file write failure

## Type B Order Processor Tests
- [x] Process order successfully when data >= 50 and amount < 100
- [x] Set status to pending when data < 50
- [x] Set status to pending when flag is true regardless of data
- [x] Set status to error when data >= 50 and amount >= 100
- [x] Handle API error response
- [x] Handle API exception
- [x] Validate order ID is positive
- [x] Validate API response data type
- [x] Handle null response data from API
- [x] Handle negative response data
- [x] Handle large integer response data
- [x] Handle API timeout
- [x] Maintain order notes during API processing
- [x] Handle non-numeric API response data
- [x] Handle failed API response status

## Type C Order Processor Tests 
- [x] Set status to in_progress when flag is false
- [x] Set status to completed when flag is true
- [x] Throw exception for invalid order data
- [x] Handle null flag
- [x] Set high priority for orders over 200
- [x] Set low priority for orders under or equal to 200
- [x] Maintain order notes during processing
- [x] Handle transitions between statuses
- [x] Should validate flag is boolean

## Order Processing Service Tests
- [x] Process type A orders with CSV export success
- [x] Verify processing order sequence with order IDs
- [x] Process type C order and set status to in_progress when flag is false
- [x] Set high priority for orders with amount greater than 200
- [x] Handle API exceptions gracefully
- [x] Handle database exceptions
- [x] Handle file operation exceptions
- [x] Handle invalid order types
- [x] Verify behavior with empty order list
- [x] Handle negative amounts
- [x] Handle database errors in saveOrder
- [x] Successfully save order status
- [x] Handle multiple orders with database updates
- [x] Handle database update with retried orders
- [x] Handle initial database query failure
- [x] Handle processor creation failure
- [x] Handle processor execution failure
- [x] Handle multiple exceptions in batch processing
- [x] Handle order with null amount
- [x] Process orders in correct sequence
- [x] Handle multiple orders with different types
- [x] Handle database exception during status update
- [x] Handle order with null flag

## Order Processor Factory Tests
- [x] Create TypeA processor
- [x] Create TypeB processor
- [x] Create TypeC processor 
- [x] Throw exception for unknown type

## Real File System Tests
- [x] Check if directory exists
- [x] Create directory 
- [x] Check if path is writable
- [x] Open and close file
- [x] Write CSV data
- [x] Flush file buffer