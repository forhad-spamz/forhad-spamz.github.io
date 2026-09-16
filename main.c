#include<stdio.h>
int main()
{
float weight,height_feet,height_cm,height_m,bmi;
printf("Enter your weight in kg:");
scanf("%f",&weight);
printf("Enter your height in feet:");
scanf("%f",&height_feet);
height_cm=height_feet*30;
height_m=height_cm/100;
bmi=weight/(height_m*height_m);
printf("your BMI is:%2f",bmi);
if(bmi<18.5)
{
printf("\nUnderweight");
}
else if (bmi>=18.5 && bmi<=24.9)
{
printf("\nNormalweight");
}
else if(bmi>=25 && bmi<=29.9)
{
printf("\nOverweight");
}
else
{
printf("\nobese");
}
return 0;
}
