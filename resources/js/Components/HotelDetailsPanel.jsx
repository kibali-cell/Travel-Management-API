import React, { useState } from 'react';
import {  IoLocationOutline, IoWifiOutline } from 'react-icons/io5';
import { BiRestaurant } from 'react-icons/bi';
import { MdLocalParking, MdOutlineFreeBreakfast } from 'react-icons/md';
import { FaArrowAltCircleLeft, FaStar } from 'react-icons/fa';
import CheckOutPanel from './CheckoutPanel';
import mapImage from '../../../assets/map.png';
import AddMoreFlights from './AddMoreFlights';

const HotelDetailsPanel = ({ isOpen, onClose, hotel }) => {
  const [openCheckoutPanel, setOpenCheckoutPanel] = useState(false);

  if (!isOpen || !hotel) return null;

  const amenitiesList = hotel.amenities || [];
  const totalPrice = hotel.price || 0;

  const renderStars = (count) => {
    return [...Array(count)].map((_, index) => (
      <FaStar key={index} className="text-yellow-400 w-3 h-3" />
    ));
  };

  const renderAmenityIcon = (amenity) => {
    switch (amenity.toLowerCase()) {
      case 'wifi':
        return <IoWifiOutline />;
      case 'restaurant':
        return <BiRestaurant />;
      case 'breakfast':
        return <MdOutlineFreeBreakfast />;
      case 'parking':
        return <MdLocalParking />;
      default:
        return <IoWifiOutline />;
    }
  };

  const reviews = [
    {
      rating: hotel.ratings?.overallRating || 0,
      author: "Verified Guest",
      comment: "Based on " + (hotel.ratings?.numberOfReviews || 0) + " reviews"
    }
  ];

  return (
    <div className="fixed inset-0 bg-white z-50 overflow-y-auto font-inter px-20">
      {/* Header Bar */}
      <div className="top-0 w-full bg-white">
        <div className="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
          <div className="flex flex-col">
            <div className="flex items-center">
              <button
                onClick={onClose}
                className="p-2 hover:bg-sectionBg rounded-full text-secondaryText"
              >
                <FaArrowAltCircleLeft className="w-6 h-6" />
              </button>
              <h2 className="text-heading-2 text-primaryText">{hotel.name}</h2>
            </div>
            <div className="flex items-center text-secondaryText text-label-2-medium mt-1">
              <IoLocationOutline className="mr-1" />
              <span>{hotel.address?.countryCode || 'Location not specified'}</span>
            </div>
          </div>

          <div className="flex items-center">
            <span className="text-heading-3 text-primaryText mr-2">{hotel.ratings?.overallRating || 0}</span>
            <div className="flex">{renderStars(hotel.stars || 0)}</div>
          </div>
        </div>
      </div>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 py-6 min-h-screen">
        {/* Image Grid */}
        <div className="mb-8">
          <div className="grid grid-cols-4 gap-4">
            <div className="col-span-2 row-span-2">
              <img
                src={hotel.images?.main || 'https://via.placeholder.com/800x600'}
                alt={hotel.name}
                className="w-full h-full object-cover rounded-lg"
              />
            </div>
            {hotel.images?.thumbnails?.map((thumbnail, index) => (
              <div key={index} className="col-span-1">
                <img 
                  src={thumbnail} 
                  alt={`${hotel.name} view ${index + 1}`} 
                  className="w-full h-full object-cover rounded-lg" 
                />
              </div>
            ))}
          </div>
        </div>

        {/* Amenities */}
        <div className="mb-8">
          <div className="flex justify-between items-center mb-4">
            <h3 className="text-heading-3 text-primaryText">Amenities</h3>
            <button className="text-label-2-medium text-secondaryText">View all</button>
          </div>
          <div className="grid grid-cols-8 gap-2">
            {amenitiesList.map((amenity, index) => (
              <div key={index} className="flex flex-col items-center justify-center p-4 bg-white rounded-md border border-stroke-lightGreyBg">
                <div className="text-secondaryText text-xl mb-2">
                  {renderAmenityIcon(amenity)}
                </div>
                <span className="text-label-3-medium text-secondaryText text-center">{amenity}</span>
              </div>
            ))}
          </div>
        </div>

        {/* Map */}
        <div className="mb-8">
          <h3 className="text-heading-3 text-primaryText mb-4">Map</h3>
          <div className="w-full h-64 bg-gray-100 rounded-lg">
            <img src={mapImage} alt="Map" className="w-full h-full object-cover rounded-lg" />
          </div>
        </div>

        {/* Rooms */}
        <div className="max-w-7xl mx-auto px-4 py-6">
          <div className="space-y-4">
            {/* Room Option */}
            <div className="border border-gray-200 rounded-lg p-6">
              <div className="space-y-4">
                <div className="flex items-center mb-2">
                  <img 
                    src={hotel.images?.main || 'https://via.placeholder.com/120/120'} 
                    alt="Room" 
                    className="w-24 h-24 object-cover rounded-lg" 
                  />
                  <div className="ml-4">
                    <h4 className="text-lg font-medium">Standard Room</h4>
                    <p className="text-sm text-gray-600">Hotel ID: {hotel.hotelId}</p>
                    <button className="text-sm text-blue-600 hover:underline">See details</button>
                  </div>
                </div>

                {/* Price Display */}
                <div className="flex justify-between items-center py-4">
                  <div>
                    <div className="font-medium">Available</div>
                    <div className="text-sm text-gray-600">Last updated: {new Date(hotel.lastUpdate).toLocaleDateString()}</div>
                  </div>
                  <div className="text-right">
                    <div className="font-medium">
                      {hotel.price ? `$${hotel.price}/night` : 'Price not available'}
                    </div>
                    <button 
                      className="mt-2 px-4 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50"
                      onClick={() => setOpenCheckoutPanel(true)}
                    >
                      Select
                    </button>
                  </div>
                </div>
              </div>
            </div>

            {/* Reviews Section */}
            <div className="mt-8">
              <h3 className="text-xl font-semibold mb-4">Reviews</h3>
              <div className="space-y-6 px-2 border-2 rounded-sm py-2">
                {reviews.map((review, index) => (
                  <div key={index} className="border-b border-gray-100 pb-4">
                    <div className="flex items-center gap-2 mb-2">
                      <span className="bg-gray-100 px-2 py-1 rounded text-sm">{review.rating}</span>
                      <span className="font-medium">{review.author}</span>
                    </div>
                    <p className="text-gray-600">{review.comment}</p>
                  </div>
                ))}
                <button className="text-blue-600 hover:underline text-sm">
                  See all reviews
                </button>
              </div>
            </div>

            {/* Bottom Bar */}
            <div className="bottom-0 left-0 right-0 bg-white border-t border-gray-200 p-4">
              <div className="max-w-7xl mx-auto flex justify-between items-center">
                <button className="text-gray-600 hover:underline">
                  Cancel
                </button>
                <div className="flex items-center gap-4">
                  <div>
                    <span className="text-sm text-gray-600">Total</span>
                    <div className="font-semibold">
                      {hotel.price ? `$${hotel.price}` : 'Price not available'}
                    </div>
                  </div>
                  <button 
                    className="px-6 py-3 bg-gray-900 text-white rounded-lg hover:bg-gray-800"
                    onClick={() => setOpenCheckoutPanel(true)}
                  >
                    Add Hotel to trip
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <AddMoreFlights
        isOpen={openCheckoutPanel}
        onClose={() => setOpenCheckoutPanel(false)}
      />
    </div>
  );
};

export default HotelDetailsPanel; 