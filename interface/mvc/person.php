var personObject = {"persons":[{
  "person_id": 44,
  "firstName": "Chris",
  "lastName" :"Paulus",
  "Addresses": [
    { "type_id" : 13,
      "address_id":144,
      "priority":1,
      "street_line_1":"123 E St.",
      "street_line_2":"Apt 23",
      "city":"Tampa",
      "state":"Florida",
      "postal":"02025-2344"
    },
    {
      "type_id" : 14,
      "address_id":145,
      "priority":2,
      "street_line_1":"44 Baker St.",
      "street_line_2":"",
      "city":"Plant City",
      "state":"Florida",
      "postal":"02025-2344"
    },
  ],
  "PhoneNums":[
    {
      "phone_id":5,
      "address_id":13,
      "type_id" : 14,
      "priority":2,
      "telephone_number":"81322244400"
    }, 
    {
      "phone_id":7,
      "address_id":13,
      "type_id" : 14,
      "priority":1,
      "telephone_number":"7274445511"
    }
  ], 
  "SecondaryContacts" : [
    {
      "person_id": 45,
      "priority":1,
      "relationship":"wife"
    },
    {
      "person_id": 47,
      "priority":2,
      "relationship":"cousin"
    }
  ]
}]};
