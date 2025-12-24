# UML Message Flows

```uml
@startuml
actor User
participant "Controller" as C
participant "AmortizationModel" as M
participant "DataProviderInterface" as D
participant "PlatformAdaptor" as A

User -> C: Submit loan details
C -> M: createLoan(data)
M -> D: insertLoan(data)
D -> A: DB insert
A --> D: Success/ID
D --> M: Loan ID
M --> C: Loan ID
C --> User: Confirmation

User -> C: Request schedule
C -> M: calculateSchedule(loan_id)
M -> D: insertSchedule(loan_id, schedule_row)
D -> A: DB insert
A --> D: Success
D --> M: Done
M --> C: Done
C --> User: Schedule displayed
@enduml
```

---
