export const RoomStatus = {
  AVAILABLE: 'available',
  OCCUPIED: 'occupied',
  MAINTENANCE: 'maintenance',
  RESERVED: 'reserved'
}

export const createRoom = (data) => {
  return {
    id: data.id || null,
    name: data.name || '',
    room_type_id: data.room_type_id || null,
    nightly_price: data.nightly_price || 0,
    number_of_people: data.number_of_people || 1, // Added number_of_people
    status: data.status || RoomStatus.AVAILABLE,
    image_url: data.image_url || '',
    location: data.location || '',
    room_number: data.room_number || '',
    pavilion_id: data.pavilion_id || null,
    service_id: data.service_id || null,
    created_at: data.created_at || null,
    updated_at: data.updated_at || null
  }
}

export const validateRoom = (room) => {
  const errors = []
  
  if (!room.name?.trim()) {
    errors.push('Room name is required')
  }
  
  if (!room.room_number?.trim()) {
    errors.push('Room number is required')
  }
  
  if (!room.location?.trim()) {
    errors.push('Location is required')
  }
  
  if (room.nightly_price === undefined || room.nightly_price === null || room.nightly_price <= 0) {
    errors.push('Nightly price must be greater than 0')
  }

  // New validation for number_of_people
  if (room.number_of_people === undefined || room.number_of_people === null || room.number_of_people < 1 || room.number_of_people > 10) {
    errors.push('Number of people must be between 1 and 10')
  }
  
  if (!Object.values(RoomStatus).includes(room.status)) {
    errors.push('Invalid room status')
  }
  
  // Basic validation for image_url if it's required.
  // In a real app, you might validate if it's a valid URL format or if a file was actually uploaded.
  // For now, let's just check if it's present if a new one isn't being uploaded.
  // if (!room.image_url?.trim() && !room.selectedFile) { // `selectedFile` is on component, not model. So this needs careful handling.
  //   errors.push('Room image is required');
  // }
  
  return {
    isValid: errors.length === 0,
    errors
  }
}

export const getStatusClasses = (status) => {
  switch (status) {
    case RoomStatus.AVAILABLE:
      return 'bg-success'
    case RoomStatus.OCCUPIED:
      return 'bg-danger'
    case RoomStatus.MAINTENANCE:
      return 'bg-warning text-dark'
    case RoomStatus.RESERVED:
      return 'bg-info'
    default:
      return 'bg-secondary'
  }
}

export const formatPrice = (price) => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'CHF' // Changed to CHF as Switzerland is the current location
  }).format(price)
}

// Sample data for testing
export const getSampleRooms = () => {
  const sampleRooms = [
    {
      id: 1,
      name: 'Deluxe Suite',
      room_number: '101',
      location: 'North Wing',
      nightly_price: 250,
      number_of_people: 2, // Added to sample data
      status: RoomStatus.AVAILABLE,
      image_url: 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=400',
      room_type_id: 1,
      pavilion_id: 1,
      service_id: 1
    },
    {
      id: 2,
      name: 'Standard Room',
      room_number: '102',
      location: 'South Wing',
      nightly_price: 150,
      number_of_people: 1, // Added to sample data
      status: RoomStatus.OCCUPIED,
      image_url: 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=400',
      room_type_id: 2,
      pavilion_id: 1,
      service_id: 1
    },
    {
      id: 3,
      name: 'Presidential Suite',
      room_number: '301',
      location: 'Top Floor',
      nightly_price: 500,
      number_of_people: 4, // Added to sample data
      status: RoomStatus.AVAILABLE,
      image_url: 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=400',
      room_type_id: 3,
      pavilion_id: 1,
      service_id: 1
    },
    {
      id: 4,
      name: 'Economy Room',
      room_number: '201',
      location: 'East Wing',
      nightly_price: 100,
      number_of_people: 1, // Added to sample data
      status: RoomStatus.MAINTENANCE,
      image_url: 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=400',
      room_type_id: 4,
      pavilion_id: 1,
      service_id: 1
    },
    {
      id: 5,
      name: 'Family Suite',
      room_number: '202',
      location: 'West Wing',
      nightly_price: 300,
      number_of_people: 5, // Added to sample data
      status: RoomStatus.RESERVED,
      image_url: 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=400',
      room_type_id: 5,
      pavilion_id: 1,
      service_id: 1
    }
  ]
  
  return sampleRooms.map(room => createRoom(room))
}